<?php

namespace App\Models;

use App\Notifications\SocioActivadoNotification;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Laravel\Cashier\Subscription;
use Throwable;

class ClubUser extends Model
{
    use HasFactory;

    protected $table = 'club_user';

    protected $fillable = [
        'user_id',
        'club_id',
        'role',
        'is_guide',
        'status',
        'subscription_name',
        'stripe_subscription_id',
        'subscribed_at',
        'current_period_end',
        'ends_at',
        'joined_at',
        'left_at',
        'left_reason',
    ];

    protected $casts = [
        'is_guide' => 'boolean',
        'subscribed_at' => 'datetime',
        'current_period_end' => 'datetime',
        'ends_at' => 'datetime',
        'joined_at' => 'date',
        'left_at' => 'date',
    ];

    public const ROLE_ADMIN = 'admin_club';

    public const ROLE_SOCIO = 'socio';

    public const STATUS_PENDING = 'pending';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_GRACE = 'grace';

    public const STATUS_INACTIVE = 'inactive';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_GRACE], true);
    }

    public static function buildSubscriptionName(string $kind, int $clubId): string
    {
        return $kind.'-'.$clubId;
    }

    public static function parseSubscriptionName(?string $name): ?array
    {
        if (! $name) {
            return null;
        }

        // Convención Cashier: "club-{id}" (admin) o "socio-{id}" (membresía de club).
        if (! preg_match('/^(club|socio)-(\d+)$/', $name, $m)) {
            return null;
        }

        return [
            'kind' => $m[1],
            'club_id' => (int) $m[2],
            'role' => $m[1] === 'club' ? self::ROLE_ADMIN : self::ROLE_SOCIO,
        ];
    }

    /**
     * Fin de periodo para mostrar al usuario: Stripe (current_period_end) o,
     * si falta, un año después de la fecha de alta/pago (cuota anual).
     */
    public function effectiveCurrentPeriodEnd(): ?Carbon
    {
        if ($this->current_period_end !== null) {
            return $this->current_period_end;
        }

        if ($this->subscribed_at !== null) {
            return $this->subscribed_at->copy()->addYear();
        }

        return null;
    }

    /**
     * Actualiza club_user desde la suscripción de Cashier (Stripe).
     */
    public static function syncFromCashierSubscription(Subscription $subscription, bool $deleted = false): ?self
    {
        // Cashier 16 usa la columna `type` (antes `name`).
        $parsed = self::parseSubscriptionName($subscription->type);
        if (! $parsed) {
            return null;
        }

        if (! Club::find($parsed['club_id'])) {
            return null;
        }

        $membership = self::firstOrCreate(
            [
                'user_id' => $subscription->user_id,
                'club_id' => $parsed['club_id'],
                'role' => $parsed['role'],
            ],
            [
                'status' => self::STATUS_PENDING,
                'subscription_name' => $subscription->type,
                'joined_at' => now()->toDateString(),
            ]
        );

        $status = self::statusFromCashierSubscription($subscription, $deleted);

        $stripePeriodEnd = null;
        try {
            $stripeSub = $subscription->asStripeSubscription();
            $ts = $stripeSub->current_period_end
                ?? ($stripeSub->items->data[0]->current_period_end ?? null);
            if ($ts) {
                $stripePeriodEnd = Carbon::createFromTimestamp($ts);
            }
        } catch (Throwable $e) {
            $stripePeriodEnd = null;
        }

        $wasActivating = $membership->status === self::STATUS_PENDING
            && in_array($status, [self::STATUS_ACTIVE, self::STATUS_GRACE], true);

        $membership->fill([
            'subscription_name' => $subscription->type,
            'stripe_subscription_id' => $subscription->stripe_id,
            'status' => $status,
            'subscribed_at' => $membership->subscribed_at ?? now(),
            'current_period_end' => $stripePeriodEnd,
            'ends_at' => $subscription->ends_at,
        ])->save();

        // Enviar email de bienvenida la primera vez que la membresía se activa.
        if ($wasActivating) {
            try {
                $user = $membership->user;
                $club = Club::find($parsed['club_id']);
                if ($user && $club) {
                    $user->notify(new SocioActivadoNotification($club, $parsed['role']));
                }
            } catch (Throwable $e) {
                // No bloquear el flujo si falla el email
            }
        }

        return $membership->fresh();
    }

    public static function statusFromCashierSubscription(Subscription $subscription, bool $deleted = false): string
    {
        if ($deleted) {
            return self::STATUS_INACTIVE;
        }
        if ($subscription->onGracePeriod()) {
            return self::STATUS_GRACE;
        }
        if ($subscription->canceled() && ! $subscription->onGracePeriod()) {
            return self::STATUS_CANCELLED;
        }
        if (in_array($subscription->stripe_status, ['active', 'trialing'], true)) {
            return self::STATUS_ACTIVE;
        }

        return self::STATUS_INACTIVE;
    }

    /**
     * Fallback cuando el webhook de Cashier no llegó: consulta Stripe directamente,
     * crea el registro local de Cashier y sincroniza la membresía.
     */
    public static function syncFromStripeApi(User $user, string $subscriptionName): ?self
    {
        if (! $user->hasStripeId()) {
            return null;
        }

        try {
            $stripe = $user->stripe();
            $stripeSubs = $stripe->subscriptions->all([
                'customer' => $user->stripe_id,
                'status' => 'all',
                'limit' => 50,
            ]);

            foreach ($stripeSubs->data as $stripeSub) {
                $metaName = $stripeSub->metadata['subscription_name'] ?? null;
                if ($metaName !== $subscriptionName) {
                    continue;
                }

                $local = Subscription::where('stripe_id', $stripeSub->id)->first();
                if (! $local) {
                    // Reconstruir el registro local que el webhook no creó (desarrollo sin stripe listen).
                    $firstItem = $stripeSub->items->data[0] ?? null;
                    $local = $user->subscriptions()->create([
                        'type' => $subscriptionName,
                        'stripe_id' => $stripeSub->id,
                        'stripe_status' => $stripeSub->status,
                        'stripe_price' => $firstItem?->price->id,
                        'quantity' => $firstItem?->quantity ?? 1,
                        'trial_ends_at' => $stripeSub->trial_end
                            ? Carbon::createFromTimestamp($stripeSub->trial_end)
                            : null,
                        'ends_at' => null,
                    ]);

                    foreach ($stripeSub->items->data as $item) {
                        $local->items()->create([
                            'stripe_id' => $item->id,
                            'stripe_product' => $item->price->product ?? '',
                            'stripe_price' => $item->price->id,
                            'quantity' => $item->quantity ?? 1,
                        ]);
                    }
                } else {
                    $local->update(['stripe_status' => $stripeSub->status]);
                }

                return self::syncFromCashierSubscription($local->fresh());
            }
        } catch (Throwable $e) {
            return null;
        }

        return null;
    }
}

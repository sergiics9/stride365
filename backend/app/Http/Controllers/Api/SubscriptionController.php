<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubUser;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Cashier\Subscription;
use Throwable;

class SubscriptionController extends Controller
{
    public function memberships(Request $request): JsonResponse
    {
        $user = $request->user();

        $visibleStatuses = [ClubUser::STATUS_PENDING, ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE];

        $buildQuery = fn() => $user->memberships()
            ->with('club:id,nombre,slug,logo_url,active,application_status')
            ->whereIn('status', $visibleStatuses)
            ->whereHas('club', fn($q) => $q->where('application_status', '!=', Club::STATUS_REJECTED))
            ->orderBy('role')
            ->orderBy('club_id');

        $rows = $buildQuery()->get();

        // Sincronizar con Stripe: pendientes que ya están pagadas en Cashier, y fin de periodo faltante
        $synced = false;
        foreach ($rows as $membership) {
            if (! $membership->subscription_name) {
                continue;
            }
            try {
                $sub = $user->subscription($membership->subscription_name);

                // Si no existe suscripción local de Cashier pero la membresía sigue pendiente,
                // el webhook no llegó: consultamos Stripe directamente.
                if (! $sub && $membership->status === ClubUser::STATUS_PENDING) {
                    if (ClubUser::syncFromStripeApi($user, $membership->subscription_name)) {
                        $synced = true;
                    }

                    continue;
                }
                if (! $sub) {
                    continue;
                }
                if (
                    $membership->status === ClubUser::STATUS_PENDING
                    && in_array($sub->stripe_status, ['active', 'trialing'], true)
                ) {
                    ClubUser::syncFromCashierSubscription($sub);
                    $synced = true;

                    continue;
                }
                if ($membership->status === ClubUser::STATUS_ACTIVE && ! $membership->current_period_end) {
                    ClubUser::syncFromCashierSubscription($sub);
                    $synced = true;
                }
            } catch (Throwable $e) {
                // Stripe no disponible
            }
        }

        if ($synced) {
            $rows = $buildQuery()->get();
        }

        $items = $rows->map(fn(ClubUser $m) => $this->serializeMembership($m));

        return response()->json([
            'memberships' => $items,
            'has_admin_membership' => $rows->contains(fn($m) => $m->role === ClubUser::ROLE_ADMIN),
        ]);
    }

    public function status(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::in(['club', 'socio'])],
            'club_id' => ['required', 'integer', 'exists:clubes,id'],
        ]);

        $name = ClubUser::buildSubscriptionName($validated['kind'], $validated['club_id']);

        return response()->json($this->buildStatusPayload($request->user(), $name));
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::in(['club', 'socio'])],
            'club_id' => ['required', 'integer', 'exists:clubes,id'],
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
        ]);

        $user = $request->user();
        $club = Club::findOrFail($validated['club_id']);

        if ($validated['kind'] === 'club') {
            $existingAdmin = $user->getAdminMembership();
            if ($existingAdmin && $existingAdmin->club_id !== $club->id) {
                throw ValidationException::withMessages([
                    'club_id' => 'Solo puedes administrar un club a la vez.',
                ]);
            }
        }

        if ($validated['kind'] === 'socio') {
            if ($user->isAdminOfClub($club->id)) {
                throw ValidationException::withMessages([
                    'club_id' => 'No puedes ser socio de un club que administras.',
                ]);
            }
        }

        $priceKey = $validated['kind'] === 'club' ? 'club' : 'socio';
        $priceId = config("stripe.prices.$priceKey");

        if (! $priceId || str_starts_with($priceId, 'price_REPLACE')) {
            throw ValidationException::withMessages([
                'price' => "El priceId de Stripe para '{$priceKey}' no está configurado.",
            ]);
        }

        $name = ClubUser::buildSubscriptionName($validated['kind'], $club->id);

        if ($user->subscribed($name)) {
            throw ValidationException::withMessages([
                'kind' => 'Ya tienes una suscripción activa para este recurso.',
            ]);
        }

        $user->createOrGetStripeCustomer();

        $separator = str_contains($validated['success_url'], '?') ? '&' : '?';

        $checkout = $user
            ->newSubscription($name, $priceId)
            ->checkout([
                'success_url' => $validated['success_url'] . $separator . 'session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $validated['cancel_url'],
                'metadata' => [
                    'subscription_name' => $name,
                    'kind' => $validated['kind'],
                    'club_id' => (string) $club->id,
                    'user_id' => (string) $user->id,
                ],
                'subscription_data' => [
                    'metadata' => [
                        'subscription_name' => $name,
                        'kind' => $validated['kind'],
                        'club_id' => (string) $club->id,
                        'user_id' => (string) $user->id,
                    ],
                ],
            ]);

        ClubUser::firstOrCreate(
            [
                'user_id' => $user->id,
                'club_id' => $club->id,
                'role' => $validated['kind'] === 'club' ? ClubUser::ROLE_ADMIN : ClubUser::ROLE_SOCIO,
            ],
            [
                'status' => ClubUser::STATUS_PENDING,
                'subscription_name' => $name,
                'joined_at' => now()->toDateString(),
            ]
        );

        return response()->json([
            'id' => $checkout->id,
            'url' => $checkout->url,
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::in(['club', 'socio'])],
            'club_id' => ['required', 'integer', 'exists:clubes,id'],
        ]);

        $user = $request->user();
        $name = ClubUser::buildSubscriptionName($validated['kind'], $validated['club_id']);

        if (! $user->subscribed($name)) {
            return response()->json(['message' => 'No hay suscripción activa.'], 404);
        }

        $user->subscription($name)->cancel();
        ClubUser::syncFromCashierSubscription($user->fresh()->subscription($name));

        return response()->json([
            'message' => 'Suscripción cancelada al final del periodo.',
            'status' => $this->buildStatusPayload($user, $name),
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'kind' => ['required', Rule::in(['club', 'socio'])],
            'club_id' => ['required', 'integer', 'exists:clubes,id'],
        ]);

        $user = $request->user();
        $name = ClubUser::buildSubscriptionName($validated['kind'], $validated['club_id']);
        $subscription = $user->subscription($name);

        if (! $subscription || ! $subscription->onGracePeriod()) {
            return response()->json(['message' => 'No hay suscripción reanudable.'], 422);
        }

        $subscription->resume();
        ClubUser::syncFromCashierSubscription($user->fresh()->subscription($name));

        return response()->json([
            'message' => 'Suscripción reanudada.',
            'status' => $this->buildStatusPayload($user, $name),
        ]);
    }

    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->hasStripeId()) {
            return response()->json([]);
        }

        $invoices = $user->invoices()->map(fn($invoice) => [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'total' => $invoice->total(),
            'date' => $invoice->date()?->toDateTimeString(),
            'subscription' => $this->extractSubscriptionMetadata($invoice),
            'download_url' => route('api.subscription.invoices.download', ['invoice' => $invoice->id]),
        ]);

        return response()->json($invoices);
    }

    public function downloadInvoice(Request $request, string $invoice)
    {
        return $request->user()->downloadInvoice($invoice, [
            'vendor' => config('app.name'),
            'product' => 'Suscripción Stride365',
        ]);
    }

    private function buildStatusPayload($user, string $name): array
    {
        $subscription = $user->subscription($name);
        $parsed = ClubUser::parseSubscriptionName($name);

        $currentPeriodEnd = null;
        if ($subscription && $subscription->stripe_status === 'active') {
            try {
                $stripeSub = $subscription->asStripeSubscription();
                $periodEnd = $stripeSub->current_period_end
                    ?? ($stripeSub->items->data[0]->current_period_end ?? null);
                if ($periodEnd) {
                    $currentPeriodEnd = Carbon::createFromTimestamp($periodEnd)->toIso8601String();
                }
            } catch (Throwable $e) {
                $currentPeriodEnd = null;
            }
        }

        return [
            'name' => $name,
            'kind' => $parsed['kind'] ?? null,
            'club_id' => $parsed['club_id'] ?? null,
            'subscribed' => $user->subscribed($name),
            'on_trial' => $user->onTrial($name),
            'on_grace_period' => $subscription?->onGracePeriod() ?? false,
            'cancelled' => $subscription?->canceled() ?? false,
            'ends_at' => $subscription?->ends_at,
            'current_period_end' => $currentPeriodEnd,
            'stripe_status' => $subscription?->stripe_status,
            'stripe_price' => $subscription?->stripe_price,
        ];
    }

    private function serializeMembership(ClubUser $m): array
    {
        return [
            'id' => $m->id,
            'club_id' => $m->club_id,
            'club' => $m->club ? [
                'id' => $m->club->id,
                'nombre' => $m->club->nombre,
                'slug' => $m->club->slug,
                'logo_url' => $m->club->logo_url,
                'active' => $m->club->active,
                'application_status' => $m->club->application_status,
            ] : null,
            'role' => $m->role,
            'is_guide' => $m->is_guide,
            'status' => $m->status,
            'subscription_name' => $m->subscription_name,
            'subscribed_at' => $m->subscribed_at?->toIso8601String(),
            'current_period_end' => $m->effectiveCurrentPeriodEnd()?->toIso8601String(),
            'ends_at' => $m->ends_at?->toIso8601String(),
            'kind' => $m->role === ClubUser::ROLE_ADMIN ? 'club' : 'socio',
        ];
    }

    private function extractSubscriptionMetadata($invoice): ?array
    {
        try {
            $stripe = $invoice->asStripeInvoice();
            $subId = $stripe->subscription ?? null;
            if (! $subId) {
                return null;
            }

            $local = Subscription::where('stripe_id', $subId)->first();
            if (! $local) {
                return null;
            }

            $parsed = ClubUser::parseSubscriptionName($local->type);

            return [
                'name' => $local->type,
                'kind' => $parsed['kind'] ?? null,
                'club_id' => $parsed['club_id'] ?? null,
            ];
        } catch (Throwable $e) {
            return null;
        }
    }
}

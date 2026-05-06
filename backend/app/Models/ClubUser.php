<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

        if (! preg_match('/^(club|socio)-(\d+)$/', $name, $m)) {
            return null;
        }

        return [
            'kind' => $m[1],
            'club_id' => (int) $m[2],
            'role' => $m[1] === 'club' ? self::ROLE_ADMIN : self::ROLE_SOCIO,
        ];
    }
}

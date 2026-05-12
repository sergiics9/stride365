<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Club extends Model
{
    use HasFactory;

    protected $table = 'clubes';

    protected $fillable = [
        'nombre',
        'slug',
        'descripcion',
        'logo_url',
        'direccion',
        'telefono',
        'email',
        'active',
        'application_status',
        'requested_by',
        'approved_by',
        'approved_at',
        'rejection_reason',
    ];

    protected $casts = [
        'active' => 'boolean',
        'approved_at' => 'datetime',
    ];


    protected function logoUrl(): Attribute
    {
        return Attribute::make(
            get: function (?string $value): ?string {
                if ($value === null || $value === '') {
                    return null;
                }

                $path = parse_url($value, PHP_URL_PATH);
                if (! is_string($path) || ! str_starts_with($path, '/storage/')) {
                    return $value;
                }

                $request = request();
                if ($request === null || $request->getSchemeAndHttpHost() === '') {
                    return $value;
                }

                $query = parse_url($value, PHP_URL_QUERY);

                return $request->getSchemeAndHttpHost() . $path . ($query !== null ? '?' . $query : '');
            },
        );
    }

    public const STATUS_PENDING = 'pending';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    public function memberships(): HasMany
    {
        return $this->hasMany(ClubUser::class);
    }

    public function admins(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_user')
            ->wherePivot('role', ClubUser::ROLE_ADMIN)
            ->withPivot([
                'role',
                'status',
                'is_guide',
                'subscription_name',
                'stripe_subscription_id',
                'subscribed_at',
                'current_period_end',
                'ends_at',
            ])
            ->withTimestamps();
    }

    public function socios(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'club_user')
            ->wherePivot('role', ClubUser::ROLE_SOCIO)
            ->withPivot([
                'role',
                'status',
                'is_guide',
                'subscription_name',
                'stripe_subscription_id',
                'subscribed_at',
                'current_period_end',
                'ends_at',
                'joined_at',
                'left_at',
                'left_reason',
            ])
            ->withTimestamps();
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function actividades(): HasMany
    {
        return $this->hasMany(Actividad::class);
    }

    public function comunicados(): HasMany
    {
        return $this->hasMany(Comunicado::class);
    }

    public function isApproved(): bool
    {
        return $this->application_status === self::STATUS_APPROVED;
    }

    public function isActive(): bool
    {
        return $this->active && $this->isApproved();
    }
}

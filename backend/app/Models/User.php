<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Throwable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use Billable, HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'sexo',
        'telefono',
        'email',
        'password',
        'foto_url',
        'direccion',
        'fecha_alta',
        'estado',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'fecha_nacimiento' => 'date',
            'fecha_alta' => 'date',
            'password' => 'hashed',
        ];
    }

    /**
     * Misma lógica que {@see Club}: URLs bajo /storage/… con el origen de la petición actual.
     */
    protected function fotoUrl(): Attribute
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

                return $request->getSchemeAndHttpHost().$path.($query !== null ? '?'.$query : '');
            },
        );
    }

    public function getNameAttribute(): string
    {
        return trim(($this->nombre ?? '').' '.($this->apellido ?? ''));
    }

    public function memberships(): HasMany
    {
        return $this->hasMany(ClubUser::class);
    }

    public function adminMembership(): HasMany
    {
        return $this->memberships()->where('role', ClubUser::ROLE_ADMIN);
    }

    public function socioMemberships(): HasMany
    {
        return $this->memberships()->where('role', ClubUser::ROLE_SOCIO);
    }

    public function getAdminMembership(): ?ClubUser
    {
        return $this->memberships()
            ->where('role', ClubUser::ROLE_ADMIN)
            ->first();
    }

    public function getSocioMembership(int $clubId): ?ClubUser
    {
        return $this->memberships()
            ->where('role', ClubUser::ROLE_SOCIO)
            ->where('club_id', $clubId)
            ->first();
    }

    public function isAdminOfClub(int $clubId): bool
    {
        if ($this->memberships()
            ->where('role', ClubUser::ROLE_ADMIN)
            ->where('club_id', $clubId)
            ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
            ->exists()) {
            return true;
        }

        $pending = $this->memberships()
            ->where('role', ClubUser::ROLE_ADMIN)
            ->where('club_id', $clubId)
            ->where('status', ClubUser::STATUS_PENDING)
            ->first();

        if (! $pending) {
            return false;
        }

        try {
            return $this->subscribed(ClubUser::buildSubscriptionName('club', $clubId));
        } catch (Throwable $e) {
            return false;
        }
    }

    public function isSocioOfClub(int $clubId): bool
    {
        // Admin activo del club: mismo acceso que un socio (sin fila duplicada en club_user).
        return $this->isAdminOfClub($clubId) || $this->memberships()
            ->where('role', ClubUser::ROLE_SOCIO)
            ->where('club_id', $clubId)
            ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
            ->exists();
    }

    public function isGuideOfClub(int $clubId): bool
    {
        if ($this->isAdminOfClub($clubId)) {
            return true;
        }

        return $this->memberships()
            ->where('role', ClubUser::ROLE_SOCIO)
            ->where('club_id', $clubId)
            ->where('is_guide', true)
            ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
            ->exists();
    }

    public function adminClubId(): ?int
    {
        return $this->memberships()
            ->where('role', ClubUser::ROLE_ADMIN)
            ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
            ->value('club_id');
    }

    public function clubIdsAsMember(): Collection
    {
        return $this->memberships()
            ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
            ->pluck('club_id')
            ->unique()
            ->values();
    }

    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'grupo_user')
            ->using(GrupoUser::class)
            ->withPivot('id', 'fecha_union');
    }

    public function comunicados(): HasMany
    {
        return $this->hasMany(Comunicado::class);
    }

    public function publicacionesFeed(): HasMany
    {
        return $this->hasMany(PublicacionFeed::class);
    }

    public function cuotas(): HasMany
    {
        return $this->hasMany(Cuota::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function informes(): HasMany
    {
        return $this->hasMany(Informe::class);
    }
}

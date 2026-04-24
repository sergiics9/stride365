<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles, Billable;

    protected $fillable = [
        'club_id',
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

    public function getNameAttribute(): string
    {
        return trim(($this->nombre ?? '').' '.($this->apellido ?? ''));
    }

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
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

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['super_admin', 'admin_club']);
    }
}

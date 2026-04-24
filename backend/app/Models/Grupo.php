<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = [
        'club_id',
        'nombre',
        'descripcion',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'grupo_user')
            ->using(GrupoUser::class)
            ->withPivot('id', 'fecha_union');
    }

    public function comunicados(): HasMany
    {
        return $this->hasMany(Comunicado::class);
    }
}

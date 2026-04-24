<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Actividad extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'actividades';

    protected $fillable = [
        'club_id',
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'lugar',
        'modalidad',
        'distancia',
        'dificultad',
        'cupo_maximo',
        'costo',
        'estado',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'distancia' => 'decimal:2',
        'costo' => 'decimal:2',
    ];

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'inscripciones')
            ->withPivot('id', 'fecha_inscripcion');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublicacionFeed extends Model
{
    use HasFactory;

    protected $table = 'publicaciones_feed';

    protected $fillable = [
        'user_id',
        'titulo',
        'contenido',
        'imagen_url',
        'tipo',
        'visibilidad',
        'fecha_publicacion',
        'estado',
    ];

    protected $casts = [
        'fecha_publicacion' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Pago extends Model
{
    use HasFactory;

    protected $fillable = [
        'cuota_id',
        'fecha_pago',
        'monto_pagado',
        'metodo_pago',
        'referencia',
        'estado',
        'observaciones',
    ];

    protected $casts = [
        'fecha_pago' => 'datetime',
        'monto_pagado' => 'decimal:2',
    ];

    public function cuota(): BelongsTo
    {
        return $this->belongsTo(Cuota::class);
    }
}

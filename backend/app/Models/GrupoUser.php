<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class GrupoUser extends Pivot
{
    protected $table = 'grupo_user';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'grupo_id',
        'fecha_union',
    ];

    protected $casts = [
        'fecha_union' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }
}

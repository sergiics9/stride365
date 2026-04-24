<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePagoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_club']) ?? false;
    }

    public function rules(): array
    {
        return [
            'fecha_pago' => ['nullable', 'date'],
            'monto_pagado' => ['required', 'numeric', 'min:0'],
            'metodo_pago' => ['nullable', 'string', 'max:100'],
            'referencia' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'in:pendiente,confirmado,rechazado,reembolsado'],
            'observaciones' => ['nullable', 'string'],
        ];
    }
}

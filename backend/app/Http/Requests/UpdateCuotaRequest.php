<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCuotaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_club']) ?? false;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['sometimes', 'required', 'integer', 'exists:users,id'],
            'periodo' => ['nullable', 'string', 'max:50'],
            'concepto' => ['nullable', 'string', 'max:255'],
            'monto' => ['sometimes', 'required', 'numeric', 'min:0'],
            'fecha_vencimiento' => ['sometimes', 'required', 'date'],
            'estado' => ['nullable', 'string', 'in:pendiente,pagada,vencida,anulada'],
        ];
    }
}

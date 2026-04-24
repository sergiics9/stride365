<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComunicadoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_club', 'guia']) ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'contenido' => ['sometimes', 'required', 'string'],
            'fecha_publicacion' => ['nullable', 'date'],
        ];
    }
}

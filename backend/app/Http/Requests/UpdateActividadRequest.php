<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_club', 'guia']) ?? false;
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['sometimes', 'required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'modalidad' => ['nullable', 'string', 'max:100'],
            'distancia' => ['nullable', 'numeric', 'min:0'],
            'dificultad' => ['nullable', 'string', 'in:facil,media,dificil,extrema'],
            'cupo_maximo' => ['nullable', 'integer', 'min:1'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable', 'string', 'in:programada,en_curso,finalizada,cancelada'],
            'motivo_cancelacion' => ['nullable', 'string', 'max:500'],
            'imagen' => ['nullable', 'image', 'max:5120'],
        ];
    }
}

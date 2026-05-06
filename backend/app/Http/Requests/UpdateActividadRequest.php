<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActividadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }
        if ($user->hasRole('super_admin')) {
            return true;
        }
        $club = $this->route('club');
        if (! $club) {
            return false;
        }
        $clubId = (int) $club->id;

        return $user->isAdminOfClub($clubId) || $user->isGuideOfClub($clubId);
    }

    public function rules(): array
    {
        return [
            'titulo' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['sometimes', 'nullable', 'string'],
            'fecha_inicio' => ['sometimes', 'required', 'date'],
            'fecha_fin' => ['sometimes', 'nullable', 'date', 'after_or_equal:fecha_inicio'],
            'lugar' => ['sometimes', 'nullable', 'string', 'max:255'],
            'punto_encuentro' => ['sometimes', 'nullable', 'string', 'max:255'],
            'material_necesario' => ['sometimes', 'nullable', 'string'],
            'modalidad' => ['sometimes', 'nullable', 'string', 'max:100'],
            'distancia' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dificultad' => ['sometimes', 'nullable', 'string', 'in:facil,media,dificil,extrema'],
            'cupo_maximo' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'costo' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'estado' => ['sometimes', 'nullable', 'string', 'in:programada,en_curso,finalizada,cancelada'],
            'modo_creacion' => ['sometimes', 'nullable', 'string', 'in:dibujada,importada'],
            'track_geojson' => ['sometimes', 'nullable', 'array'],
            'motivo_cancelacion' => ['sometimes', 'nullable', 'string', 'max:500'],
            'guia_ids' => ['sometimes', 'array'],
            'guia_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}

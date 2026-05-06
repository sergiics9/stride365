<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreActividadRequest extends FormRequest
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
            'titulo' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['nullable', 'date', 'after_or_equal:fecha_inicio'],
            'lugar' => ['nullable', 'string', 'max:255'],
            'punto_encuentro' => ['nullable', 'string', 'max:255'],
            'material_necesario' => ['nullable', 'string'],
            'modalidad' => ['nullable', 'string', 'max:100'],
            'distancia' => ['nullable', 'numeric', 'min:0'],
            'dificultad' => ['nullable', 'string', 'in:facil,media,dificil,extrema'],
            'cupo_maximo' => ['nullable', 'integer', 'min:1'],
            'costo' => ['nullable', 'numeric', 'min:0'],
            'estado' => ['nullable', 'string', 'in:programada,en_curso,finalizada,cancelada'],
            'modo_creacion' => ['nullable', 'string', 'in:dibujada,importada'],
            'track_geojson' => ['nullable', 'array'],
            'guia_ids' => ['nullable', 'array'],
            'guia_ids.*' => ['integer', 'exists:users,id'],
        ];
    }
}

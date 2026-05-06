<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateComunicadoRequest extends FormRequest
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
            'contenido' => ['sometimes', 'required', 'string'],
            'fecha_publicacion' => ['sometimes', 'nullable', 'date'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreComunicadoRequest extends FormRequest
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
            'contenido' => ['required', 'string'],
            'fecha_publicacion' => ['nullable', 'date'],
        ];
    }
}

<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'string', 'max:255'],
            'apellido' => ['sometimes', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:50'],
            'fecha_nacimiento' => ['sometimes', 'nullable', 'date'],
            'sexo' => ['sometimes', 'nullable', 'string', 'max:20'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:500'],
            'foto' => ['sometimes', 'nullable', 'image', 'mimes:jpeg,jpg,png,webp,gif', 'max:2048'],
        ];
    }
}

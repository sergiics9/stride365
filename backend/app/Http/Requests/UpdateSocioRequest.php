<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateSocioRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['super_admin', 'admin_club']) ?? false;
    }

    public function rules(): array
    {
        $socio = $this->route('socio');
        $socioId = is_object($socio) ? $socio->id : $socio;

        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'apellido' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($socioId)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'fecha_nacimiento' => ['nullable', 'date'],
            'sexo' => ['nullable', 'string', 'in:hombre,mujer,otro'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'estado' => ['nullable', 'string', 'in:activo,inactivo,baja'],
            'motivo_baja' => ['nullable', 'string', 'max:500'],
            'rol' => ['nullable', 'string', 'in:admin_club,guia,socio'],
        ];
    }
}

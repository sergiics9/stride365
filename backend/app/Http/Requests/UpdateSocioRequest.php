<?php

namespace App\Http\Requests;

use App\Models\ClubUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSocioRequest extends FormRequest
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

        return $club && $user->isAdminOfClub((int) $club->id);
    }

    public function rules(): array
    {
        return [
            'nombre' => ['sometimes', 'nullable', 'string', 'max:255'],
            'apellido' => ['sometimes', 'nullable', 'string', 'max:255'],
            'telefono' => ['sometimes', 'nullable', 'string', 'max:50'],
            'direccion' => ['sometimes', 'nullable', 'string', 'max:255'],
            'fecha_nacimiento' => ['sometimes', 'nullable', 'date'],
            'sexo' => ['sometimes', 'nullable', 'string', 'in:M,F,O'],
            'is_guide' => ['sometimes', 'boolean'],
            'status' => [
                'sometimes',
                'string',
                Rule::in([
                    ClubUser::STATUS_PENDING,
                    ClubUser::STATUS_ACTIVE,
                    ClubUser::STATUS_INACTIVE,
                    ClubUser::STATUS_GRACE,
                    ClubUser::STATUS_CANCELLED,
                ]),
            ],
        ];
    }
}

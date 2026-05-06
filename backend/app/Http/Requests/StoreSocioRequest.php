<?php

namespace App\Http\Requests;

use App\Models\ClubUser;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSocioRequest extends FormRequest
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
            'email' => ['required', 'email', 'max:255'],
            'nombre' => ['nullable', 'string', 'max:255'],
            'apellido' => ['nullable', 'string', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:8'],
            'status' => [
                'nullable',
                'string',
                Rule::in([
                    ClubUser::STATUS_PENDING,
                    ClubUser::STATUS_ACTIVE,
                    ClubUser::STATUS_INACTIVE,
                ]),
            ],
        ];
    }
}

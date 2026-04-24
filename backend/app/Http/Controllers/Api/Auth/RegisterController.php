<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiRegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class RegisterController extends Controller
{
    public function __invoke(ApiRegisterRequest $request): JsonResponse
    {
        $user = User::create([
            'club_id' => $request->input('club_id'),
            'nombre' => $request->string('nombre'),
            'apellido' => $request->string('apellido'),
            'email' => $request->string('email'),
            'telefono' => $request->input('telefono'),
            'password' => $request->string('password'),
            'fecha_alta' => now()->toDateString(),
            'estado' => 'activo',
        ]);

        $user->assignRole('socio');

        $device = $request->string('device_name')->value() ?: $request->userAgent() ?? 'api';
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles'),
        ], 201);
    }
}

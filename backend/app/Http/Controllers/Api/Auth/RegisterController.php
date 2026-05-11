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
        $fotoUrl = null;
        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('users/avatars', 'public');
            $fotoUrl = $request->getSchemeAndHttpHost().'/storage/'.$path;
        }

        $user = User::create([
            'nombre' => $request->string('nombre'),
            'apellido' => $request->string('apellido'),
            'email' => $request->string('email'),
            'telefono' => $request->input('telefono'),
            'sexo' => $request->input('sexo'),
            'fecha_nacimiento' => $request->input('fecha_nacimiento'),
            'direccion' => $request->input('direccion'),
            'password' => $request->string('password'),
            'foto_url' => $fotoUrl,
            'fecha_alta' => now()->toDateString(),
            'estado' => 'activo',
        ]);

        $user->assignRole('usuario');

        $device = $request->string('device_name')->value() ?: $request->userAgent() ?? 'api';
        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $user->load('roles'),
        ], 201);
    }
}

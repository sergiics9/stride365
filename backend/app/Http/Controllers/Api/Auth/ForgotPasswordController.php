<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ApiForgotPasswordRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Password;

class ForgotPasswordController extends Controller
{
    public function __invoke(ApiForgotPasswordRequest $request): JsonResponse
    {
        $status = Password::sendResetLink($request->only('email'));

        if ($status === Password::RESET_THROTTLED) {
            return response()->json([
                'message' => __($status),
                'errors' => [
                    'email' => [__($status)],
                ],
            ], 429);
        }

        return response()->json([
            'message' => 'Si existe una cuenta con ese correo, recibirás un enlace para restablecer la contraseña.',
        ]);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscribed
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        if (! $user->subscribed('default')) {
            return response()->json([
                'message' => 'Requiere suscripción activa para acceder al módulo Clubes.',
                'code' => 'SUBSCRIPTION_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}

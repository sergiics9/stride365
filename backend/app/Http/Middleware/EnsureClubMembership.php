<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClubMembership
{
    public function handle(Request $request, Closure $next, string $roles = 'admin,socio,guide'): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => 'No autenticado.'], 401);
        }

        if ($user->hasRole('super_admin')) {
            return $next($request);
        }

        $clubId = $this->resolveClubId($request);
        if (! $clubId) {
            return $next($request);
        }

        $accepted = array_filter(array_map('trim', explode(',', $roles)));
        $matches = false;
        foreach ($accepted as $role) {
            $matches = match ($role) {
                'admin' => $user->isAdminOfClub($clubId),
                'socio' => $user->isSocioOfClub($clubId),
                'guide' => $user->isGuideOfClub($clubId),
                default => false,
            };
            if ($matches) {
                break;
            }
        }

        if (! $matches) {
            return response()->json([
                'message' => 'No tienes acceso a este club.',
                'code' => 'CLUB_ACCESS_DENIED',
            ], 403);
        }

        return $next($request);
    }

    private function resolveClubId(Request $request): ?int
    {
        $param = $request->route('club') ?? $request->route('clubId');
        if (is_object($param) && property_exists($param, 'id')) {
            return (int) $param->id;
        }
        if (is_numeric($param)) {
            return (int) $param;
        }

        $body = $request->input('club_id');
        if (is_numeric($body)) {
            return (int) $body;
        }

        return null;
    }
}

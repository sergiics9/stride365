<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\ClubUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->load([
            'roles',
            'memberships.club:id,nombre,slug,logo_url,active,application_status',
        ]);

        $memberships = $user->memberships->map(fn (ClubUser $m) => [
            'id' => $m->id,
            'club_id' => $m->club_id,
            'club' => $m->club ? [
                'id' => $m->club->id,
                'nombre' => $m->club->nombre,
                'slug' => $m->club->slug,
                'logo_url' => $m->club->logo_url,
                'active' => $m->club->active,
                'application_status' => $m->club->application_status,
            ] : null,
            'role' => $m->role,
            'is_guide' => $m->is_guide,
            'status' => $m->status,
            'kind' => $m->role === ClubUser::ROLE_ADMIN ? 'club' : 'socio',
            'subscription_name' => $m->subscription_name,
            'subscribed_at' => $m->subscribed_at?->toIso8601String(),
            'current_period_end' => $m->current_period_end?->toIso8601String(),
            'ends_at' => $m->ends_at?->toIso8601String(),
        ]);

        return response()->json([
            'user' => $user->makeHidden(['memberships']),
            'roles' => $user->getRoleNames(),
            'memberships' => $memberships,
        ]);
    }
}

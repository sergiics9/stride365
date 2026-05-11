<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UserController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $visibleStatuses = [ClubUser::STATUS_PENDING, ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE];

        $membershipsQuery = fn ($q) => $q
            ->with('club:id,nombre,slug,logo_url,active,application_status')
            ->whereIn('status', $visibleStatuses)
            ->whereHas('club', fn ($q) => $q->where('application_status', '!=', Club::STATUS_REJECTED));

        $user = $request->user()->load([
            'roles',
            'memberships' => $membershipsQuery,
        ]);

        $synced = false;
        foreach ($user->memberships as $membership) {
            if ($membership->status !== ClubUser::STATUS_PENDING || ! $membership->subscription_name) {
                continue;
            }
            try {
                $sub = $user->subscription($membership->subscription_name);
                if ($sub && in_array($sub->stripe_status, ['active', 'trialing'], true)) {
                    ClubUser::syncFromCashierSubscription($sub);
                    $synced = true;
                }
            } catch (Throwable $e) {
                // Stripe no disponible o suscripción aún no enlazada
            }
        }

        if ($synced) {
            $user->unsetRelation('memberships');
            $user->load(['memberships' => $membershipsQuery]);
        }

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
            'current_period_end' => $m->effectiveCurrentPeriodEnd()?->toIso8601String(),
            'ends_at' => $m->ends_at?->toIso8601String(),
        ]);

        return response()->json([
            'user' => $user->makeHidden(['memberships']),
            'roles' => $user->getRoleNames(),
            'memberships' => $memberships,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateClubRequest;
use App\Models\Club;
use App\Models\ClubUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Club::query()
            ->withCount([
                'memberships as socios_count' => fn($q) => $q
                    ->where('role', ClubUser::ROLE_SOCIO)
                    ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE]),
                'grupos',
                'actividades',
            ]);

        if (! $user || ! $user->hasRole('super_admin')) {
            $query->where('active', true)
                ->where('application_status', Club::STATUS_APPROVED);
        }

        if ($search = $request->query('q')) {
            $query->where('nombre', 'like', "%$search%");
        }

        return response()->json($query->orderBy('nombre')->paginate(15));
    }

    public function show(Request $request, Club $club): JsonResponse
    {
        $user = $request->user();

        $isSuperAdmin = $user && $user->hasRole('super_admin');
        $isMember = $user && ($user->isAdminOfClub($club->id) || $user->isSocioOfClub($club->id));

        // Solo público si está activo + aprobado, o si el usuario es miembro/admin.
        if (! $isSuperAdmin && ! $isMember && ! $club->isActive()) {
            abort(404);
        }

        return response()->json(
            $club->loadCount([
                'memberships as socios_count' => fn($q) => $q
                    ->where('role', ClubUser::ROLE_SOCIO)
                    ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE]),
                'grupos',
                'actividades',
            ])
        );
    }

    public function update(UpdateClubRequest $request, Club $club): JsonResponse
    {
        $this->authorizeClubManage($request, $club);

        $club->update($request->validated());

        return response()->json($club);
    }

    public function destroy(Request $request, Club $club): JsonResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        $club->delete();

        return response()->json(['message' => 'Club eliminado.']);
    }

    private function authorizeClubManage(Request $request, Club $club): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        abort_unless($user->isAdminOfClub($club->id), 403, 'No puedes editar este club.');
    }
}

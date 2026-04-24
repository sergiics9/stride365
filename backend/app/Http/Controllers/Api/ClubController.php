<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClubRequest;
use App\Http\Requests\UpdateClubRequest;
use App\Models\Club;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Club::query()->withCount(['users', 'grupos', 'actividades']);

        if (! $user->hasRole('super_admin') && $user->club_id) {
            $query->where('id', $user->club_id);
        }

        return response()->json($query->orderBy('nombre')->paginate(15));
    }

    public function store(StoreClubRequest $request): JsonResponse
    {
        $club = Club::create($request->validated());

        return response()->json($club, 201);
    }

    public function show(Request $request, Club $club): JsonResponse
    {
        $this->authorizeClubAccess($request, $club);

        return response()->json($club->loadCount(['users', 'grupos', 'actividades']));
    }

    public function update(UpdateClubRequest $request, Club $club): JsonResponse
    {
        $this->authorizeClubAccess($request, $club);

        $club->update($request->validated());

        return response()->json($club);
    }

    public function destroy(Request $request, Club $club): JsonResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        $club->delete();

        return response()->json(['message' => 'Club eliminado.']);
    }

    private function authorizeClubAccess(Request $request, Club $club): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        abort_unless($user->club_id === $club->id, 403, 'No perteneces a este club.');
    }
}

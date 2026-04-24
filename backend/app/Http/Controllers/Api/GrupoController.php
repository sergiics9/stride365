<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGrupoRequest;
use App\Http\Requests\UpdateGrupoRequest;
use App\Models\Club;
use App\Models\Grupo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GrupoController extends Controller
{
    public function index(Request $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        return response()->json(
            $club->grupos()->withCount('users')->orderBy('nombre')->paginate(30)
        );
    }

    public function store(StoreGrupoRequest $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $grupo = $club->grupos()->create($request->validated());

        return response()->json($grupo, 201);
    }

    public function show(Request $request, Club $club, Grupo $grupo): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($grupo->club_id === $club->id, 404);

        return response()->json(
            $grupo->load(['users:id,nombre,apellido,email', 'comunicados'])
        );
    }

    public function update(UpdateGrupoRequest $request, Club $club, Grupo $grupo): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($grupo->club_id === $club->id, 404);

        $grupo->update($request->validated());

        return response()->json($grupo);
    }

    public function destroy(Request $request, Club $club, Grupo $grupo): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($grupo->club_id === $club->id, 404);

        $grupo->delete();

        return response()->json(['message' => 'Grupo eliminado.']);
    }

    private function authorizeClub(Request $request, Club $club): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        abort_unless(
            $user->hasRole('admin_club') && $user->club_id === $club->id,
            403
        );
    }
}

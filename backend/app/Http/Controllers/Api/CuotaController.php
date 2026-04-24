<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCuotaRequest;
use App\Http\Requests\UpdateCuotaRequest;
use App\Models\Club;
use App\Models\Cuota;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CuotaController extends Controller
{
    public function index(Request $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $query = Cuota::query()
            ->whereHas('user', fn ($q) => $q->where('club_id', $club->id))
            ->with('user:id,nombre,apellido,email,club_id');

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($userId = $request->query('user_id')) {
            $query->where('user_id', $userId);
        }

        return response()->json($query->orderByDesc('fecha_vencimiento')->paginate(30));
    }

    public function store(StoreCuotaRequest $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $data = $request->validated();

        $socio = \App\Models\User::findOrFail($data['user_id']);
        abort_unless($socio->club_id === $club->id, 422, 'El socio no pertenece al club.');

        $cuota = Cuota::create($data);

        return response()->json($cuota->load('user:id,nombre,apellido,email'), 201);
    }

    public function show(Request $request, Club $club, Cuota $cuota): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($cuota->user?->club_id === $club->id, 404);

        return response()->json($cuota->load(['user:id,nombre,apellido,email', 'pagos']));
    }

    public function update(UpdateCuotaRequest $request, Club $club, Cuota $cuota): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($cuota->user?->club_id === $club->id, 404);

        $cuota->update($request->validated());

        return response()->json($cuota);
    }

    public function destroy(Request $request, Club $club, Cuota $cuota): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($cuota->user?->club_id === $club->id, 404);

        $cuota->delete();

        return response()->json(['message' => 'Cuota eliminada.']);
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

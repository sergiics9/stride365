<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActividadRequest;
use App\Http\Requests\UpdateActividadRequest;
use App\Models\Actividad;
use App\Models\Club;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ActividadController extends Controller
{
    public function index(Request $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $query = $club->actividades()->withCount('inscripciones');

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($dificultad = $request->query('dificultad')) {
            $query->where('dificultad', $dificultad);
        }

        return response()->json($query->orderBy('fecha_inicio', 'desc')->paginate(20));
    }

    public function store(StoreActividadRequest $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $data = $request->validated();
        $data['club_id'] = $club->id;
        $data['estado'] = $data['estado'] ?? 'programada';

        $actividad = Actividad::create($data);

        if ($request->hasFile('imagen')) {
            $actividad->addMediaFromRequest('imagen')->toMediaCollection('imagenes');
        }

        if ($request->hasFile('gpx_file')) {
            $actividad->addMediaFromRequest('gpx_file')->toMediaCollection('gpx');
        }

        return response()->json($actividad->load('media'), 201);
    }

    public function show(Request $request, Club $club, Actividad $actividad): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($actividad->club_id === $club->id, 404);

        return response()->json(
            $actividad->load(['media', 'inscripciones.user:id,nombre,apellido,email'])
        );
    }

    public function update(UpdateActividadRequest $request, Club $club, Actividad $actividad): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($actividad->club_id === $club->id, 404);

        $actividad->update($request->validated());

        if ($request->hasFile('imagen')) {
            $actividad->clearMediaCollection('imagenes');
            $actividad->addMediaFromRequest('imagen')->toMediaCollection('imagenes');
        }

        return response()->json($actividad->load('media'));
    }

    public function destroy(Request $request, Club $club, Actividad $actividad): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($actividad->club_id === $club->id, 404);

        $motivo = $request->input('motivo_cancelacion');

        $actividad->update([
            'estado' => 'cancelada',
        ]);

        return response()->json([
            'message' => 'Actividad cancelada.',
            'motivo' => $motivo,
        ]);
    }

    private function authorizeClub(Request $request, Club $club): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        abort_unless(
            $user->hasAnyRole(['admin_club', 'guia']) && $user->club_id === $club->id,
            403
        );
    }
}

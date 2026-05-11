<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreActividadRequest;
use App\Http\Requests\UpdateActividadRequest;
use App\Models\Actividad;
use App\Models\Club;
use App\Models\ClubUser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ActividadController extends Controller
{
    public function index(Request $request, Club $club): JsonResponse
    {
        $this->authorizeRead($request, $club);

        $query = $club->actividades()
            ->withCount('inscripciones')
            ->with('guias:id,nombre,apellido,email');

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
        $this->authorizeManage($request, $club);

        $data = $request->validated();
        $guiaIds = $data['guia_ids'] ?? [];
        unset($data['guia_ids']);
        $data['club_id'] = $club->id;
        $data['estado'] = $data['estado'] ?? Actividad::ESTADO_PROGRAMADA;
        $data['modo_creacion'] = $data['modo_creacion'] ?? Actividad::MODO_DIBUJADA;

        if (array_key_exists('track_geojson', $data)) {
            $data['track_geojson'] = Actividad::hydrateTrackGeoJsonElevations($data['track_geojson']);
            $data['distancia'] = Actividad::distanciaKmDesdeTrackGeoJson($data['track_geojson']);
            $data['desnivel_positivo_m'] = Actividad::desnivelPositivoMDesdeTrackGeoJson($data['track_geojson']);
        }

        $actividad = DB::transaction(function () use ($data, $guiaIds, $club) {
            $a = Actividad::create($data);
            if (! empty($guiaIds)) {
                $valid = $this->filterGuiaIdsForClub($club, $guiaIds);
                $a->guias()->sync($valid);
            }

            return $a;
        });

        return response()->json($actividad->load('guias:id,nombre,apellido,email'), 201);
    }

    public function show(Request $request, Club $club, Actividad $actividad): JsonResponse
    {
        $this->authorizeRead($request, $club);
        abort_unless($actividad->club_id === $club->id, 404);

        return response()->json(
            $actividad->load([
                'guias:id,nombre,apellido,email',
                'inscripciones.user:id,nombre,apellido,email',
            ])
        );
    }

    public function update(UpdateActividadRequest $request, Club $club, Actividad $actividad): JsonResponse
    {
        $this->authorizeManage($request, $club);
        abort_unless($actividad->club_id === $club->id, 404);

        $data = $request->validated();
        $guiaIds = $data['guia_ids'] ?? null;
        unset($data['guia_ids']);

        if (array_key_exists('track_geojson', $data)) {
            $data['track_geojson'] = Actividad::hydrateTrackGeoJsonElevations($data['track_geojson']);
            $data['distancia'] = Actividad::distanciaKmDesdeTrackGeoJson($data['track_geojson']);
            $data['desnivel_positivo_m'] = Actividad::desnivelPositivoMDesdeTrackGeoJson($data['track_geojson']);
        }

        DB::transaction(function () use ($actividad, $data, $guiaIds, $club) {
            $actividad->update($data);
            if (is_array($guiaIds)) {
                $valid = $this->filterGuiaIdsForClub($club, $guiaIds);
                $actividad->guias()->sync($valid);
            }
        });

        return response()->json($actividad->fresh()->load('guias:id,nombre,apellido,email'));
    }

    public function destroy(Request $request, Club $club, Actividad $actividad): JsonResponse
    {
        $this->authorizeManage($request, $club);
        abort_unless($actividad->club_id === $club->id, 404);

        $motivo = $request->input('motivo_cancelacion');

        $actividad->update([
            'estado' => Actividad::ESTADO_CANCELADA,
            'motivo_cancelacion' => $motivo,
        ]);

        return response()->json([
            'message' => 'Actividad cancelada.',
            'motivo' => $motivo,
        ]);
    }

    public function finish(Request $request, Club $club, Actividad $actividad): JsonResponse
    {
        $this->authorizeManage($request, $club);
        abort_unless($actividad->club_id === $club->id, 404);

        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ]);

        if ($actividad->estado === Actividad::ESTADO_FINALIZADA) {
            return response()->json(['message' => 'La actividad ya estaba finalizada.'], 422);
        }

        DB::transaction(function () use ($actividad, $validated) {
            $actividad->update([
                'estado' => Actividad::ESTADO_FINALIZADA,
                'finalizada_at' => now(),
                'titulo' => $validated['titulo'] ?? $actividad->titulo,
                'descripcion' => $validated['descripcion'] ?? $actividad->descripcion,
            ]);
        });

        return response()->json([
            'message' => 'Actividad finalizada.',
            'actividad' => $actividad->fresh(),
        ]);
    }

    private function authorizeRead(Request $request, Club $club): void
    {
        $user = $request->user();
        if ($user->hasRole('super_admin')) {
            return;
        }
        $allowed = $user->isAdminOfClub($club->id)
            || $user->isSocioOfClub($club->id);
        abort_unless($allowed, 403);
    }

    private function authorizeManage(Request $request, Club $club): void
    {
        $user = $request->user();
        if ($user->hasRole('super_admin')) {
            return;
        }
        $allowed = $user->isAdminOfClub($club->id) || $user->isGuideOfClub($club->id);
        abort_unless($allowed, 403);
    }

    private function filterGuiaIdsForClub(Club $club, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }
        $valid = ClubUser::where('club_id', $club->id)
            ->where('role', ClubUser::ROLE_SOCIO)
            ->where('is_guide', true)
            ->whereIn('user_id', $ids)
            ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
            ->pluck('user_id')
            ->toArray();

        if (count($valid) !== count($ids)) {
            throw ValidationException::withMessages([
                'guia_ids' => 'Alguno de los guías indicados no es socio activo del club.',
            ]);
        }

        return $valid;
    }
}

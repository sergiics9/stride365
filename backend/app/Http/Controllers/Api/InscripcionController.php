<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInscripcionRequest;
use App\Models\Actividad;
use App\Models\Inscripcion;
use App\Notifications\InscripcionCanceladaNotification;
use App\Notifications\InscripcionConfirmadaNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class InscripcionController extends Controller
{
    public function index(Request $request, Actividad $actividad): JsonResponse
    {
        $this->authorizeRead($request, $actividad);

        return response()->json(
            $actividad->inscripciones()
                ->with('user:id,nombre,apellido,email,telefono')
                ->orderByDesc('fecha_inscripcion')
                ->paginate(30)
        );
    }

    public function store(StoreInscripcionRequest $request, Actividad $actividad): JsonResponse
    {
        $user = $request->user();
        $clubId = $actividad->club_id;

        $userId = $request->input('user_id', $user->id);

        if ($userId !== $user->id) {
            $canManage = $user->hasRole('super_admin')
                || $user->isAdminOfClub($clubId)
                || $user->isGuideOfClub($clubId);
            abort_unless($canManage, 403, 'Solo admin o guía pueden inscribir a otros socios.');
        } else {
            // Auto-inscripción: socio activo del club
            $isMember = $user->hasRole('super_admin')
                || $user->isAdminOfClub($clubId)
                || $user->isSocioOfClub($clubId);
            if (! $isMember) {
                throw ValidationException::withMessages([
                    'club' => 'Debes ser socio activo del club para inscribirte.',
                ]);
            }
        }

        if (in_array($actividad->estado, ['cancelada', 'finalizada'], true)) {
            throw ValidationException::withMessages([
                'actividad' => 'La actividad no admite inscripciones.',
            ]);
        }

        if ($actividad->cupo_maximo && $actividad->inscripciones()->count() >= $actividad->cupo_maximo) {
            throw ValidationException::withMessages([
                'cupo' => 'No quedan plazas disponibles.',
            ]);
        }

        $exists = Inscripcion::where('actividad_id', $actividad->id)
            ->where('user_id', $userId)
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'user_id' => 'El socio ya está inscrito en esta actividad.',
            ]);
        }

        $inscripcion = Inscripcion::create([
            'user_id' => $userId,
            'actividad_id' => $actividad->id,
            'fecha_inscripcion' => now(),
        ]);

        $inscripcion->load('user:id,nombre,apellido,email');
        $actividad->loadMissing('club:id,nombre,slug');
        $inscripcion->user?->notify(new InscripcionConfirmadaNotification($actividad));

        return response()->json($inscripcion, 201);
    }

    public function show(Request $request, Actividad $actividad, Inscripcion $inscripcion): JsonResponse
    {
        $this->authorizeRead($request, $actividad);
        abort_unless($inscripcion->actividad_id === $actividad->id, 404);

        return response()->json($inscripcion->load('user:id,nombre,apellido,email'));
    }

    public function destroy(Request $request, Actividad $actividad, Inscripcion $inscripcion): JsonResponse
    {
        abort_unless($inscripcion->actividad_id === $actividad->id, 404);

        $user = $request->user();
        $clubId = $actividad->club_id;

        $owns = $inscripcion->user_id === $user->id;
        $canManage = $user->hasRole('super_admin')
            || $user->isAdminOfClub($clubId)
            || $user->isGuideOfClub($clubId);

        abort_unless($owns || $canManage, 403);

        $motivo = $request->input('motivo');
        $socio = $inscripcion->user;

        $inscripcion->delete();
        $socio?->notify(new InscripcionCanceladaNotification($actividad, $motivo));

        return response()->json(['message' => 'Inscripción cancelada.']);
    }

    private function authorizeRead(Request $request, Actividad $actividad): void
    {
        $user = $request->user();
        if ($user->hasRole('super_admin')) {
            return;
        }
        $clubId = $actividad->club_id;
        abort_unless(
            $user->isAdminOfClub($clubId) || $user->isGuideOfClub($clubId) || $user->isSocioOfClub($clubId),
            403
        );
    }
}

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

        $userId = $request->input('user_id', $user->id);

        if ($userId !== $user->id) {
            abort_unless(
                $user->hasAnyRole(['super_admin', 'admin_club']),
                403,
                'Solo admin_club puede inscribir a otros socios.'
            );
        }

        if ($actividad->cupo_maximo && $actividad->inscripciones()->count() >= $actividad->cupo_maximo) {
            throw ValidationException::withMessages([
                'cupo' => 'No quedan plazas disponibles.',
            ]);
        }

        if ($actividad->estado === 'cancelada' || $actividad->estado === 'finalizada') {
            throw ValidationException::withMessages([
                'actividad' => 'La actividad no admite inscripciones.',
            ]);
        }

        $inscripcion = Inscripcion::firstOrCreate(
            ['user_id' => $userId, 'actividad_id' => $actividad->id],
            ['fecha_inscripcion' => now()]
        );

        $inscripcion->user->notify(new InscripcionConfirmadaNotification($actividad));

        return response()->json($inscripcion->load('user:id,nombre,apellido,email'), 201);
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

        $owns = $inscripcion->user_id === $user->id;
        $canManage = $user->hasAnyRole(['super_admin', 'admin_club']);

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

        abort_unless(
            $user->hasAnyRole(['admin_club', 'guia']) && $user->club_id === $actividad->club_id,
            403
        );
    }
}

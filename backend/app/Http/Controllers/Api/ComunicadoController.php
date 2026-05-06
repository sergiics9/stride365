<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComunicadoRequest;
use App\Http\Requests\UpdateComunicadoRequest;
use App\Models\Club;
use App\Models\Comunicado;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComunicadoController extends Controller
{
    public function index(Request $request, Club $club): JsonResponse
    {
        $this->authorizeRead($request, $club);

        return response()->json(
            $club->comunicados()
                ->with('user:id,nombre,apellido,email')
                ->orderByDesc('fecha_publicacion')
                ->paginate(20)
        );
    }

    public function store(StoreComunicadoRequest $request, Club $club): JsonResponse
    {
        $this->authorizeManage($request, $club);

        $data = $request->validated();
        $data['club_id'] = $club->id;
        $data['user_id'] = $request->user()->id;
        $data['fecha_publicacion'] = $data['fecha_publicacion'] ?? now();

        $comunicado = Comunicado::create($data);

        return response()->json($comunicado->load('user:id,nombre,apellido,email'), 201);
    }

    public function show(Request $request, Club $club, Comunicado $comunicado): JsonResponse
    {
        $this->authorizeRead($request, $club);
        abort_unless($comunicado->club_id === $club->id, 404);

        return response()->json($comunicado->load('user:id,nombre,apellido,email'));
    }

    public function update(UpdateComunicadoRequest $request, Club $club, Comunicado $comunicado): JsonResponse
    {
        $this->authorizeManage($request, $club);
        abort_unless($comunicado->club_id === $club->id, 404);

        $comunicado->update($request->validated());

        return response()->json($comunicado);
    }

    public function destroy(Request $request, Club $club, Comunicado $comunicado): JsonResponse
    {
        $this->authorizeManage($request, $club);
        abort_unless($comunicado->club_id === $club->id, 404);

        $comunicado->delete();

        return response()->json(['message' => 'Comunicado eliminado.']);
    }

    private function authorizeRead(Request $request, Club $club): void
    {
        $user = $request->user();
        if ($user->hasRole('super_admin')) {
            return;
        }
        $allowed = $user->isAdminOfClub($club->id) || $user->isSocioOfClub($club->id);
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
}

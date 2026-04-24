<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreComunicadoRequest;
use App\Http\Requests\UpdateComunicadoRequest;
use App\Models\Comunicado;
use App\Models\Grupo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ComunicadoController extends Controller
{
    public function index(Request $request, Grupo $grupo): JsonResponse
    {
        $this->authorizeGrupo($request, $grupo);

        return response()->json(
            $grupo->comunicados()
                ->with('user:id,nombre,apellido,email')
                ->orderByDesc('fecha_publicacion')
                ->paginate(20)
        );
    }

    public function store(StoreComunicadoRequest $request, Grupo $grupo): JsonResponse
    {
        $this->authorizeGrupo($request, $grupo);

        $data = $request->validated();
        $data['user_id'] = $request->user()->id;
        $data['fecha_publicacion'] = $data['fecha_publicacion'] ?? now();

        $comunicado = $grupo->comunicados()->create($data);

        return response()->json($comunicado->load('user:id,nombre,apellido,email'), 201);
    }

    public function show(Request $request, Grupo $grupo, Comunicado $comunicado): JsonResponse
    {
        $this->authorizeGrupo($request, $grupo);
        abort_unless($comunicado->grupo_id === $grupo->id, 404);

        return response()->json($comunicado->load('user:id,nombre,apellido,email'));
    }

    public function update(UpdateComunicadoRequest $request, Grupo $grupo, Comunicado $comunicado): JsonResponse
    {
        $this->authorizeGrupo($request, $grupo);
        abort_unless($comunicado->grupo_id === $grupo->id, 404);

        $comunicado->update($request->validated());

        return response()->json($comunicado);
    }

    public function destroy(Request $request, Grupo $grupo, Comunicado $comunicado): JsonResponse
    {
        $this->authorizeGrupo($request, $grupo);
        abort_unless($comunicado->grupo_id === $grupo->id, 404);

        $comunicado->delete();

        return response()->json(['message' => 'Comunicado eliminado.']);
    }

    private function authorizeGrupo(Request $request, Grupo $grupo): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        $sameClub = $grupo->club_id && $user->club_id === $grupo->club_id;

        abort_unless(
            $user->hasAnyRole(['admin_club', 'guia']) && $sameClub,
            403
        );
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSocioRequest;
use App\Http\Requests\UpdateSocioRequest;
use App\Models\Club;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SocioController extends Controller
{
    public function index(Request $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $query = $club->users()->with('roles');

        if ($estado = $request->query('estado')) {
            $query->where('estado', $estado);
        }

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                    ->orWhere('apellido', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        return response()->json($query->orderBy('apellido')->paginate(20));
    }

    public function store(StoreSocioRequest $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $data = $request->validated();
        $rol = $data['rol'] ?? 'socio';
        unset($data['rol'], $data['password_confirmation']);

        $data['club_id'] = $club->id;
        $data['fecha_alta'] = $data['fecha_alta'] ?? now()->toDateString();
        $data['estado'] = $data['estado'] ?? 'activo';

        $socio = User::create($data);
        $socio->assignRole($rol);

        return response()->json($socio->load('roles'), 201);
    }

    public function show(Request $request, Club $club, User $socio): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($socio->club_id === $club->id, 404);

        return response()->json($socio->load(['roles', 'grupos', 'inscripciones.actividad', 'cuotas']));
    }

    public function update(UpdateSocioRequest $request, Club $club, User $socio): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($socio->club_id === $club->id, 404);

        $data = $request->validated();
        $rol = $data['rol'] ?? null;
        unset($data['rol'], $data['password_confirmation']);

        if (empty($data['password'])) {
            unset($data['password']);
        }

        if (($data['estado'] ?? null) === 'baja') {
            $data['fecha_alta'] = $socio->fecha_alta;
        }

        $socio->update($data);

        if ($rol) {
            $socio->syncRoles([$rol]);
        }

        return response()->json($socio->load('roles'));
    }

    public function destroy(Request $request, Club $club, User $socio): JsonResponse
    {
        $this->authorizeClub($request, $club);
        abort_unless($socio->club_id === $club->id, 404);

        $socio->update([
            'estado' => 'baja',
        ]);

        return response()->json(['message' => 'Socio dado de baja.']);
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

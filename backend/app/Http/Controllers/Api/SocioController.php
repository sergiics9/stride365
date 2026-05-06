<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSocioRequest;
use App\Http\Requests\UpdateSocioRequest;
use App\Models\Club;
use App\Models\ClubUser;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class SocioController extends Controller
{
    public function index(Request $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $query = $club->memberships()
            ->where('role', ClubUser::ROLE_SOCIO)
            ->with('user:id,nombre,apellido,email,telefono,fecha_alta,estado,foto_url');

        if ($estado = $request->query('estado')) {
            $query->where('status', $estado);
        }

        if ($guide = $request->query('guide')) {
            $query->where('is_guide', filter_var($guide, FILTER_VALIDATE_BOOLEAN));
        }

        if ($search = $request->query('q')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('nombre', 'like', "%$search%")
                    ->orWhere('apellido', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        $paginator = $query->orderBy('id', 'desc')->paginate(20);

        $paginator->getCollection()->transform(fn (ClubUser $m) => $this->serialize($m));

        return response()->json($paginator);
    }

    public function store(StoreSocioRequest $request, Club $club): JsonResponse
    {
        $this->authorizeClub($request, $club);

        $data = $request->validated();
        $email = $data['email'];

        $user = User::where('email', $email)->first();

        if (! $user) {
            $user = User::create([
                'nombre' => $data['nombre'] ?? null,
                'apellido' => $data['apellido'] ?? null,
                'email' => $email,
                'telefono' => $data['telefono'] ?? null,
                'password' => Hash::make($data['password'] ?? str()->random(16)),
                'fecha_alta' => now()->toDateString(),
                'estado' => 'activo',
            ]);
            if (! $user->hasRole('usuario')) {
                $user->assignRole('usuario');
            }
        }

        $existing = ClubUser::where('user_id', $user->id)
            ->where('club_id', $club->id)
            ->where('role', ClubUser::ROLE_SOCIO)
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'email' => 'Este usuario ya es socio del club.',
            ]);
        }

        $membership = ClubUser::create([
            'user_id' => $user->id,
            'club_id' => $club->id,
            'role' => ClubUser::ROLE_SOCIO,
            'status' => $data['status'] ?? ClubUser::STATUS_PENDING,
            'is_guide' => false,
            'joined_at' => now()->toDateString(),
        ]);

        $membership->load('user:id,nombre,apellido,email,telefono,fecha_alta,estado,foto_url');

        return response()->json($this->serialize($membership), 201);
    }

    public function show(Request $request, Club $club, ClubUser $socio): JsonResponse
    {
        $this->authorizeClub($request, $club);
        $this->ensureClubMatch($club, $socio);

        $socio->load('user:id,nombre,apellido,email,telefono,fecha_alta,estado,foto_url,direccion,fecha_nacimiento,sexo');

        return response()->json($this->serialize($socio));
    }

    public function update(UpdateSocioRequest $request, Club $club, ClubUser $socio): JsonResponse
    {
        $this->authorizeClub($request, $club);
        $this->ensureClubMatch($club, $socio);

        $data = $request->validated();

        // Datos personales del usuario
        $userFields = ['nombre', 'apellido', 'telefono', 'direccion', 'fecha_nacimiento', 'sexo'];
        $userData = collect($data)->only($userFields)->filter(fn ($v) => $v !== null)->toArray();
        if (! empty($userData)) {
            $socio->user->update($userData);
        }

        // Estado de la membresía
        if (! empty($data['status'])) {
            $socio->status = $data['status'];
        }
        if (array_key_exists('is_guide', $data)) {
            if ($data['is_guide'] && ! in_array($socio->status, [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE], true)) {
                throw ValidationException::withMessages([
                    'is_guide' => 'Solo socios activos pueden ser guías.',
                ]);
            }
            $socio->is_guide = (bool) $data['is_guide'];
        }
        $socio->save();

        $socio->load('user:id,nombre,apellido,email,telefono,fecha_alta,estado,foto_url');

        return response()->json($this->serialize($socio));
    }

    public function destroy(Request $request, Club $club, ClubUser $socio): JsonResponse
    {
        $this->authorizeClub($request, $club);
        $this->ensureClubMatch($club, $socio);

        $reason = $request->input('motivo');

        $socio->update([
            'status' => ClubUser::STATUS_INACTIVE,
            'left_at' => now()->toDateString(),
            'left_reason' => $reason,
        ]);

        return response()->json(['message' => 'Socio dado de baja.']);
    }

    private function authorizeClub(Request $request, Club $club): void
    {
        $user = $request->user();
        abort_unless($user, 401);

        if ($user->hasRole('super_admin')) {
            return;
        }
        abort_unless($user->isAdminOfClub($club->id), 403, 'No administras este club.');
    }

    private function ensureClubMatch(Club $club, ClubUser $socio): void
    {
        abort_unless(
            $socio->club_id === $club->id && $socio->role === ClubUser::ROLE_SOCIO,
            404
        );
    }

    private function serialize(ClubUser $m): array
    {
        return [
            'id' => $m->id,
            'club_id' => $m->club_id,
            'user_id' => $m->user_id,
            'user' => $m->user ? [
                'id' => $m->user->id,
                'nombre' => $m->user->nombre,
                'apellido' => $m->user->apellido,
                'email' => $m->user->email,
                'telefono' => $m->user->telefono,
                'foto_url' => $m->user->foto_url,
                'fecha_alta' => $m->user->fecha_alta,
                'estado' => $m->user->estado,
                'direccion' => $m->user->direccion ?? null,
                'fecha_nacimiento' => $m->user->fecha_nacimiento ?? null,
                'sexo' => $m->user->sexo ?? null,
            ] : null,
            'role' => $m->role,
            'is_guide' => $m->is_guide,
            'status' => $m->status,
            'subscription_name' => $m->subscription_name,
            'current_period_end' => $m->current_period_end?->toIso8601String(),
            'ends_at' => $m->ends_at?->toIso8601String(),
            'joined_at' => $m->joined_at?->toDateString(),
            'left_at' => $m->left_at?->toDateString(),
            'left_reason' => $m->left_reason,
        ];
    }
}

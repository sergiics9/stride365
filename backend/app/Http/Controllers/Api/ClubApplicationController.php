<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClubApplicationRequest;
use App\Models\Club;
use App\Services\ClubApplicationActions;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClubApplicationController extends Controller
{
    public function __construct(private readonly ClubApplicationActions $actions)
    {
    }

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        $query = Club::query()
            ->with('requester:id,nombre,apellido,email')
            ->orderByDesc('created_at');

        if ($status = $request->query('status')) {
            $query->where('application_status', $status);
        } else {
            $query->where('application_status', Club::STATUS_PENDING);
        }

        return response()->json($query->paginate(20));
    }

    public function store(StoreClubApplicationRequest $request): JsonResponse
    {
        $user = $request->user();

        if ($user->getAdminMembership()) {
            throw ValidationException::withMessages([
                'club' => 'Solo puedes administrar un club a la vez.',
            ]);
        }

        $validated = $request->validated();
        unset($validated['logo']);

        $data = array_merge($validated, [
            'slug' => $this->makeUniqueSlug($validated['nombre']),
            'active' => false,
            'application_status' => Club::STATUS_PENDING,
            'requested_by' => $user->id,
            'logo_url' => null,
        ]);

        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('clubes/logos', 'public');
            $data['logo_url'] = $request->getSchemeAndHttpHost().'/storage/'.$path;
        }

        $club = Club::create($data);

        return response()->json($club, 201);
    }

    public function show(Request $request, Club $club): JsonResponse
    {
        abort_unless(
            $request->user()->hasRole('super_admin') || $club->requested_by === $request->user()->id,
            403
        );

        return response()->json($club->load('requester:id,nombre,apellido,email'));
    }

    public function approve(Request $request, Club $club): JsonResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        $result = $this->actions->approve($club, $request->user());

        if ($result === ClubApplicationActions::RESULT_ALREADY_APPROVED) {
            return response()->json(['message' => 'El club ya está aprobado.'], 422);
        }

        return response()->json([
            'message' => 'Club aprobado.',
            'club' => $club->fresh(),
        ]);
    }

    public function reject(Request $request, Club $club): JsonResponse
    {
        abort_unless($request->user()->hasRole('super_admin'), 403);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $result = $this->actions->reject($club, $request->user(), $validated['reason']);

        if ($result === ClubApplicationActions::RESULT_ALREADY_REJECTED) {
            return response()->json(['message' => 'El club ya estaba rechazado.'], 422);
        }

        return response()->json([
            'message' => 'Club rechazado.',
            'club' => $club->fresh(),
        ]);
    }

    private function makeUniqueSlug(string $nombre): string
    {
        $base = Str::slug($nombre);
        if ($base === '') {
            $base = 'club-'.Str::random(6);
        }
        $slug = $base;
        $i = 2;
        while (Club::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}

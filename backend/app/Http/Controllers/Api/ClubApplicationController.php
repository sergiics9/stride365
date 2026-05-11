<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClubApplicationRequest;
use App\Models\Club;
use App\Models\ClubUser;
use App\Models\User;
use App\Notifications\ClubApplicationApprovedNotification;
use App\Notifications\ClubApplicationRejectedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ClubApplicationController extends Controller
{
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

        if ($club->application_status === Club::STATUS_APPROVED) {
            return response()->json(['message' => 'El club ya está aprobado.'], 422);
        }

        $club->fill([
            'application_status' => Club::STATUS_APPROVED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => null,
            'active' => true,
        ])->save();

        if ($club->requested_by) {
            ClubUser::firstOrCreate(
                [
                    'user_id' => $club->requested_by,
                    'club_id' => $club->id,
                    'role' => ClubUser::ROLE_ADMIN,
                ],
                [
                    'status' => ClubUser::STATUS_PENDING,
                    'subscription_name' => ClubUser::buildSubscriptionName('club', $club->id),
                    'joined_at' => now()->toDateString(),
                ]
            );

            $requester = User::find($club->requested_by);
            if ($requester) {
                $subName = ClubUser::buildSubscriptionName('club', $club->id);
                if ($requester->subscribed($subName)) {
                    $cashierSub = $requester->subscription($subName);
                    if ($cashierSub) {
                        ClubUser::syncFromCashierSubscription($cashierSub);
                    }
                }

                $clubUrl = config('app.frontend_url').'/clubes/'.$club->id;
                $requester->notify(new ClubApplicationApprovedNotification($club->fresh(), $clubUrl));
            }
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

        if ($club->application_status === Club::STATUS_REJECTED) {
            return response()->json(['message' => 'El club ya estaba rechazado.'], 422);
        }

        $club->fill([
            'application_status' => Club::STATUS_REJECTED,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'rejection_reason' => $validated['reason'],
            'active' => false,
        ])->save();

        // Cancelar suscripción Stripe del solicitante (si existe)
        if ($club->requested_by) {
            $membership = ClubUser::where('user_id', $club->requested_by)
                ->where('club_id', $club->id)
                ->where('role', ClubUser::ROLE_ADMIN)
                ->first();

            if ($membership) {
                $user = $membership->user;
                $name = $membership->subscription_name ?? ClubUser::buildSubscriptionName('club', $club->id);
                if ($user && $user->subscribed($name)) {
                    try {
                        $user->subscription($name)->cancelNow();
                    } catch (\Throwable $e) {
                        // Si Stripe falla, marcamos localmente como cancelada.
                    }
                }
                $membership->update([
                    'status' => ClubUser::STATUS_INACTIVE,
                    'left_at' => now()->toDateString(),
                    'left_reason' => 'Solicitud rechazada: '.$validated['reason'],
                ]);
            }

            $requester = User::find($club->requested_by);
            if ($requester) {
                $requester->notify(new ClubApplicationRejectedNotification($club->fresh(), $validated['reason']));
            }
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

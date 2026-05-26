<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Models\ClubUser;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClubController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $clubes = Club::query()
            ->withCount([
                'memberships as socios_count' => function ($query) {
                    $query->where('role', ClubUser::ROLE_SOCIO)
                        ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE]);
                },
            ])
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.clubes.index', compact('clubes', 'q'));
    }

    public function show(Club $club): View
    {
        $club->load([
            'requester:id,nombre,apellido,email',
            'approver:id,nombre,apellido,email',
        ]);

        $sociosCount = $club->memberships()
            ->where('role', ClubUser::ROLE_SOCIO)
            ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
            ->count();

        $adminsCount = $club->memberships()
            ->where('role', ClubUser::ROLE_ADMIN)
            ->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE])
            ->count();

        $actividadesCount = $club->actividades()->count();

        return view('admin.clubes.show', compact('club', 'sociosCount', 'adminsCount', 'actividadesCount'));
    }

    public function toggleActive(Club $club): RedirectResponse
    {
        if ($club->application_status !== Club::STATUS_APPROVED) {
            return redirect()
                ->route('admin.clubes.show', $club)
                ->with('error', 'Solo se pueden activar o desactivar clubes aprobados.');
        }

        $club->update(['active' => ! $club->active]);

        return redirect()
            ->route('admin.clubes.show', $club)
            ->with('status', $club->active ? 'Club activado.' : 'Club desactivado.');
    }
}

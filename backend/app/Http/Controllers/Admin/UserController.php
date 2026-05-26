<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClubUser;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));

        $usuarios = User::query()
            ->withCount([
                'memberships as memberships_count' => function ($query) {
                    $query->whereIn('status', [ClubUser::STATUS_ACTIVE, ClubUser::STATUS_GRACE]);
                },
            ])
            ->with('roles:id,name')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'like', "%{$q}%")
                        ->orWhere('apellido', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.usuarios.index', compact('usuarios', 'q'));
    }

    public function show(User $usuario): View
    {
        $usuario->load([
            'roles:id,name',
            'memberships.club:id,nombre,slug',
        ]);

        return view('admin.usuarios.show', ['usuario' => $usuario]);
    }
}

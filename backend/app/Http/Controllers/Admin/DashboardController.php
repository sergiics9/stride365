<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\Club;
use App\Models\User;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function __invoke(): View
    {
        $stats = [
            'clubes_activos' => Club::where('active', true)
                ->where('application_status', Club::STATUS_APPROVED)
                ->count(),
            'solicitudes_pendientes' => Club::where('application_status', Club::STATUS_PENDING)->count(),
            'usuarios_totales' => User::count(),
            'actividades_totales' => Actividad::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}

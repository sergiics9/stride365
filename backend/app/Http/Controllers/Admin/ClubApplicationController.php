<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Club;
use App\Services\ClubApplicationActions;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ClubApplicationController extends Controller
{
    public function __construct(private readonly ClubApplicationActions $actions)
    {
    }

    public function index(Request $request): View
    {
        $status = $request->query('status', Club::STATUS_PENDING);
        $validStatuses = [Club::STATUS_PENDING, Club::STATUS_APPROVED, Club::STATUS_REJECTED];
        if (! in_array($status, $validStatuses, true)) {
            $status = Club::STATUS_PENDING;
        }

        $solicitudes = Club::query()
            ->with('requester:id,nombre,apellido,email')
            ->where('application_status', $status)
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('admin.solicitudes.index', compact('solicitudes', 'status'));
    }

    public function show(Club $club): View
    {
        $club->load([
            'requester:id,nombre,apellido,email',
            'approver:id,nombre,apellido,email',
        ]);

        return view('admin.solicitudes.show', compact('club'));
    }

    public function approve(Request $request, Club $club): RedirectResponse
    {
        $result = $this->actions->approve($club, $request->user());

        if ($result === ClubApplicationActions::RESULT_ALREADY_APPROVED) {
            return redirect()
                ->route('admin.solicitudes.show', $club)
                ->with('error', 'El club ya estaba aprobado.');
        }

        return redirect()
            ->route('admin.solicitudes.show', $club)
            ->with('status', 'Club aprobado.');
    }

    public function reject(Request $request, Club $club): RedirectResponse
    {
        $validated = $request->validate([
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $result = $this->actions->reject($club, $request->user(), $validated['reason']);

        if ($result === ClubApplicationActions::RESULT_ALREADY_REJECTED) {
            return redirect()
                ->route('admin.solicitudes.show', $club)
                ->with('error', 'El club ya estaba rechazado.');
        }

        return redirect()
            ->route('admin.solicitudes.show', $club)
            ->with('status', 'Club rechazado.');
    }
}

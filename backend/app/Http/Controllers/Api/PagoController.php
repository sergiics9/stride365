<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePagoRequest;
use App\Models\Cuota;
use App\Models\Pago;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PagoController extends Controller
{
    public function index(Request $request, Cuota $cuota): JsonResponse
    {
        $this->authorizeAccess($request, $cuota);

        return response()->json(
            $cuota->pagos()->orderByDesc('fecha_pago')->paginate(20)
        );
    }

    public function store(StorePagoRequest $request, Cuota $cuota): JsonResponse
    {
        $this->authorizeAccess($request, $cuota);

        $data = $request->validated();
        $data['fecha_pago'] = $data['fecha_pago'] ?? now();
        $data['estado'] = $data['estado'] ?? 'confirmado';

        $pago = $cuota->pagos()->create($data);

        if ($pago->estado === 'confirmado') {
            $cuota->update(['estado' => 'pagada']);
        }

        return response()->json($pago, 201);
    }

    public function show(Request $request, Cuota $cuota, Pago $pago): JsonResponse
    {
        $this->authorizeAccess($request, $cuota);
        abort_unless($pago->cuota_id === $cuota->id, 404);

        return response()->json($pago);
    }

    public function destroy(Request $request, Cuota $cuota, Pago $pago): JsonResponse
    {
        $this->authorizeAccess($request, $cuota);
        abort_unless($pago->cuota_id === $cuota->id, 404);

        $pago->delete();

        return response()->json(['message' => 'Pago eliminado.']);
    }

    private function authorizeAccess(Request $request, Cuota $cuota): void
    {
        $user = $request->user();

        if ($user->hasRole('super_admin')) {
            return;
        }

        abort_unless(
            $user->hasRole('admin_club')
                && $cuota->user
                && $cuota->user->club_id === $user->club_id,
            403
        );
    }
}

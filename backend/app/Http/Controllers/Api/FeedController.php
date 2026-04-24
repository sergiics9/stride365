<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PublicacionFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FeedController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = PublicacionFeed::query()
            ->with(['user:id,nombre,apellido,email', 'media'])
            ->where('estado', 'activo');

        if ($tipo = $request->query('tipo')) {
            $query->where('tipo', $tipo);
        }

        if ($dificultad = $request->query('dificultad')) {
            $query->where('visibilidad', $dificultad);
        }

        if ($desde = $request->query('desde')) {
            $query->whereDate('fecha_publicacion', '>=', $desde);
        }

        if ($hasta = $request->query('hasta')) {
            $query->whereDate('fecha_publicacion', '<=', $hasta);
        }

        if ($fecha = $request->query('fecha')) {
            $query->whereDate('fecha_publicacion', $fecha);
        }

        $perPage = min((int) $request->query('per_page', 15), 50);

        return response()->json(
            $query->orderByDesc('fecha_publicacion')->paginate($perPage)
        );
    }

    public function show(PublicacionFeed $publicacion): JsonResponse
    {
        abort_if($publicacion->estado !== 'activo', 404);

        return response()->json(
            $publicacion->load(['user:id,nombre,apellido,email', 'media'])
        );
    }
}

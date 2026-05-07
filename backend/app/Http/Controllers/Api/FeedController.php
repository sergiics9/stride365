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
            ->with([
                'user:id,nombre,apellido,email',
                'actividad:id,club_id,user_id,titulo,fecha_inicio,fecha_fin,distancia,desnivel_positivo_m,duracion_segundos,ritmo_segundos_por_km,pulsaciones_media,pulsaciones_max,dificultad,modalidad,track_geojson,modo_creacion',
                'actividad.club:id,nombre,slug,logo_url',
                'media',
            ])
            ->where('estado', 'activo')
            ->whereHas('actividad', static function ($q) {
                $q->whereNull('club_id');
            });

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
        abort_unless(
            $publicacion->actividad && $publicacion->actividad->club_id === null,
            404,
        );

        return response()->json(
            $publicacion->load([
                'user:id,nombre,apellido,email',
                'actividad:id,club_id,user_id,titulo,descripcion,fecha_inicio,fecha_fin,distancia,desnivel_positivo_m,duracion_segundos,ritmo_segundos_por_km,pulsaciones_media,pulsaciones_max,dificultad,modalidad,track_geojson,modo_creacion',
                'actividad.club:id,nombre,slug,logo_url',
                'media',
            ])
        );
    }
}

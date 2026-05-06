<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\PublicacionFeed;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class FeedRecordingController extends Controller
{
    public function start(Request $request): JsonResponse
    {
        $user = $request->user();
        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($user) {
            Actividad::query()
                ->where('user_id', $user->id)
                ->whereNull('club_id')
                ->where('estado', Actividad::ESTADO_EN_CURSO)
                ->update([
                    'estado' => Actividad::ESTADO_CANCELADA,
                    'motivo_cancelacion' => 'Sustituida por una nueva grabación',
                ]);
        });

        $titulo = $validated['titulo'] ?? 'Actividad en directo';

        $actividad = Actividad::create([
            'user_id' => $user->id,
            'club_id' => null,
            'titulo' => $titulo,
            'descripcion' => null,
            'fecha_inicio' => now(),
            'fecha_fin' => null,
            'estado' => Actividad::ESTADO_EN_CURSO,
            'modo_creacion' => Actividad::MODO_VIVO,
            'track_geojson' => [
                'type' => 'LineString',
                'coordinates' => [],
            ],
        ]);

        return response()->json($actividad, 201);
    }

    public function updateTrack(Request $request, int $recording): JsonResponse
    {
        $actividad = $this->personalRecordingOrFail($request, $recording);

        abort_unless($actividad->estado === Actividad::ESTADO_EN_CURSO, 422, 'La actividad no está en curso.');

        $validated = $request->validate([
            'coordinates' => ['required', 'array', 'min:1'],
            'coordinates.*' => ['array', 'size:2'],
            'coordinates.*.0' => ['numeric', 'between:-180,180'],
            'coordinates.*.1' => ['numeric', 'between:-90,90'],
        ]);

        $incoming = array_map(
            static fn(array $p) => [(float) $p[0], (float) $p[1]],
            $validated['coordinates'],
        );

        $geo = $actividad->track_geojson ?? ['type' => 'LineString', 'coordinates' => []];
        $existing = $geo['coordinates'] ?? [];
        if (! is_array($existing)) {
            $existing = [];
        }

        $merged = array_merge($existing, $incoming);
        $actividad->update([
            'track_geojson' => [
                'type' => 'LineString',
                'coordinates' => $merged,
            ],
        ]);

        return response()->json($actividad->fresh());
    }

    public function finish(Request $request, int $recording): JsonResponse
    {
        $actividad = $this->personalRecordingOrFail($request, $recording);

        abort_unless($actividad->estado === Actividad::ESTADO_EN_CURSO, 422, 'Solo se puede publicar una grabación en curso.');

        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
        ]);

        if ($actividad->estado === Actividad::ESTADO_FINALIZADA) {
            return response()->json(['message' => 'La actividad ya estaba finalizada.'], 422);
        }

        $coords = $actividad->track_geojson['coordinates'] ?? [];
        $distancia = $this->lineLengthKm($coords);

        DB::transaction(function () use ($actividad, $validated, $request, $distancia) {
            $now = now();
            $tituloFinal = $validated['titulo'] ?? $actividad->titulo;
            $descFinal = $validated['descripcion'] ?? $actividad->descripcion;

            $actividad->update([
                'estado' => Actividad::ESTADO_FINALIZADA,
                'finalizada_at' => $now,
                'fecha_fin' => $now,
                'titulo' => $tituloFinal,
                'descripcion' => $descFinal,
                'distancia' => $distancia > 0 ? round($distancia, 2) : null,
            ]);

            $resumenParts = [];
            if ($distancia > 0) {
                $resumenParts[] = round($distancia, 2) . ' km';
            }
            $dur = $actividad->fecha_inicio?->diffInMinutes($now);
            if ($dur !== null && $dur > 0) {
                $resumenParts[] = $dur . ' min';
            }

            PublicacionFeed::create([
                'user_id' => $request->user()->id,
                'actividad_id' => $actividad->id,
                'titulo' => $tituloFinal,
                'resumen' => implode(' · ', $resumenParts) ?: null,
                'contenido' => $descFinal ?? '',
                'tipo' => 'registro_personal',
                'fecha_publicacion' => $now,
                'estado' => 'activo',
            ]);
        });

        return response()->json([
            'message' => 'Actividad publicada en el feed.',
            'actividad' => $actividad->fresh(),
        ]);
    }

    public function importGpx(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'titulo' => ['nullable', 'string', 'max:255'],
            'gpx' => ['required', 'file', 'max:15360'],
        ]);

        $contents = file_get_contents($validated['gpx']->getRealPath());
        if ($contents === false || $contents === '') {
            return response()->json(['message' => 'No se pudo leer el archivo GPX.'], 422);
        }

        try {
            $geojson = $this->gpxStringToLineStringGeoJson($contents);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'GPX inválido o sin track: ' . $e->getMessage()], 422);
        }

        $coords = $geojson['coordinates'] ?? [];
        if (count($coords) < 2) {
            return response()->json(['message' => 'El GPX no contiene suficientes puntos.'], 422);
        }

        $user = $request->user();
        $distancia = $this->lineLengthKm($coords);
        $titulo = $validated['titulo'] ?? 'Actividad importada';

        $actividad = null;

        DB::transaction(function () use ($user, $titulo, $geojson, $distancia, &$actividad) {
            $now = now();
            $actividad = Actividad::create([
                'user_id' => $user->id,
                'club_id' => null,
                'titulo' => $titulo,
                'descripcion' => null,
                'fecha_inicio' => $now,
                'fecha_fin' => $now,
                'estado' => Actividad::ESTADO_FINALIZADA,
                'modo_creacion' => Actividad::MODO_IMPORTADA,
                'track_geojson' => $geojson,
                'distancia' => $distancia > 0 ? round($distancia, 2) : null,
                'finalizada_at' => $now,
            ]);

            $resumenParts = [];
            if ($distancia > 0) {
                $resumenParts[] = round($distancia, 2) . ' km';
            }

            PublicacionFeed::create([
                'user_id' => $user->id,
                'actividad_id' => $actividad->id,
                'titulo' => $actividad->titulo,
                'resumen' => implode(' · ', $resumenParts) ?: null,
                'contenido' => '',
                'tipo' => 'importacion_gpx',
                'fecha_publicacion' => now(),
                'estado' => 'activo',
            ]);
        });

        return response()->json([
            'message' => 'Actividad importada y publicada en el feed.',
            'actividad' => $actividad?->fresh(),
        ], 201);
    }

    private function personalRecordingOrFail(Request $request, int $id): Actividad
    {
        return Actividad::query()
            ->whereKey($id)
            ->whereNull('club_id')
            ->where('user_id', $request->user()->id)
            ->firstOrFail();
    }

    /**
     * @param  list<list<float|int>>  $coords
     */
    private function lineLengthKm(array $coords): float
    {
        $km = 0.0;
        $n = count($coords);
        for ($i = 1; $i < $n; $i++) {
            $a = $coords[$i - 1];
            $b = $coords[$i];
            if (count($a) < 2 || count($b) < 2) {
                continue;
            }
            $km += $this->haversineKm((float) $a[1], (float) $a[0], (float) $b[1], (float) $b[0]);
        }

        return $km;
    }

    private function haversineKm(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /**
     * @return array{type: string, coordinates: list<list<float>>}
     */
    private function gpxStringToLineStringGeoJson(string $xml): array
    {
        libxml_use_internal_errors(true);
        $sx = simplexml_load_string($xml);
        if ($sx === false) {
            throw new \InvalidArgumentException('XML inválido');
        }

        $sx->registerXPathNamespace('gpx', 'http://www.topografix.com/GPX/1/1');
        $points = $sx->xpath('//*[local-name()="trkpt"]');
        if (empty($points)) {
            $points = $sx->xpath('//gpx:trkpt');
        }

        $coordinates = [];
        foreach ($points as $pt) {
            if (! ($pt instanceof \SimpleXMLElement)) {
                continue;
            }
            $attrs = $pt->attributes();
            $lat = isset($attrs['lat']) ? (float) $attrs['lat'] : null;
            $lon = isset($attrs['lon']) ? (float) $attrs['lon'] : null;
            if ($lat === null || $lon === null) {
                continue;
            }
            $coordinates[] = [$lon, $lat];
        }

        if (count($coordinates) < 2) {
            throw new \InvalidArgumentException('No se encontraron puntos trkpt');
        }

        return [
            'type' => 'LineString',
            'coordinates' => $coordinates,
        ];
    }
}

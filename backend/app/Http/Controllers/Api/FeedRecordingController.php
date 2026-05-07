<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Actividad;
use App\Models\PublicacionFeed;
use App\Support\GpxTrackParser;
use App\Support\TrackMetrics;
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
            'coordinates.*' => ['array', 'min:2', 'max:5'],
            'coordinates.*.0' => ['required', 'numeric', 'between:-180,180'],
            'coordinates.*.1' => ['required', 'numeric', 'between:-90,90'],
            'coordinates.*.2' => ['nullable', 'numeric'],
            'coordinates.*.3' => ['nullable', 'numeric'],
            'coordinates.*.4' => ['nullable', 'integer', 'min:1', 'max:280'],
        ]);

        $incoming = array_map(
            fn (array $p) => $this->normalizeCoordinateRow($p),
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
        $now = now();

        $dur = TrackMetrics::durationSecondsFromCoords($coords);
        if ($dur === null && $actividad->fecha_inicio) {
            $dur = max(0, (int) $actividad->fecha_inicio->diffInSeconds($now));
        }

        $pace = TrackMetrics::paceSecondsPerKm($dur, $distancia);
        $dplus = TrackMetrics::positiveElevationGainMFromCoords($coords);
        $hr = TrackMetrics::heartRateStatsFromCoords($coords);

        DB::transaction(function () use (
            $actividad,
            $validated,
            $request,
            $distancia,
            $now,
            $dur,
            $pace,
            $dplus,
            $hr,
        ) {
            $tituloFinal = $validated['titulo'] ?? $actividad->titulo;
            $descFinal = $validated['descripcion'] ?? $actividad->descripcion;

            $actividad->update([
                'estado' => Actividad::ESTADO_FINALIZADA,
                'finalizada_at' => $now,
                'fecha_fin' => $now,
                'titulo' => $tituloFinal,
                'descripcion' => $descFinal,
                'distancia' => $distancia > 0 ? round($distancia, 2) : null,
                'duracion_segundos' => $dur,
                'ritmo_segundos_por_km' => $pace,
                'desnivel_positivo_m' => $dplus,
                'pulsaciones_media' => $hr['avg'],
                'pulsaciones_max' => $hr['max'],
            ]);

            $resumenParts = $this->feedResumenParts(
                $distancia,
                $dur,
                $dplus,
                $pace,
                $hr['avg'],
            );

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
            $parsed = GpxTrackParser::parse($contents);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'GPX inválido o sin track: '.$e->getMessage()], 422);
        }

        $geojson = $parsed['geojson'];
        $coords = $geojson['coordinates'] ?? [];
        if (count($coords) < 2) {
            return response()->json(['message' => 'El GPX no contiene suficientes puntos.'], 422);
        }

        $user = $request->user();
        $distancia = $this->lineLengthKm($coords);
        $titulo = $validated['titulo'] ?? 'Actividad importada';

        $startedAt = $parsed['started_at'];
        $finishedAt = $parsed['finished_at'];
        $fechaInicio = $startedAt ?? now();
        $fechaFin = $finishedAt ?? $fechaInicio;

        $dur = null;
        if ($startedAt && $finishedAt) {
            $dur = max(0, (int) $startedAt->diffInSeconds($finishedAt));
        }
        if ($dur === null) {
            $dur = TrackMetrics::durationSecondsFromCoords($coords);
        }

        $pace = TrackMetrics::paceSecondsPerKm($dur, $distancia);
        $dplus = TrackMetrics::positiveElevationGainMFromCoords($coords);

        $hrAvg = null;
        $hrMax = null;
        $hrs = $parsed['heart_rates'];
        if ($hrs !== []) {
            $hrAvg = (int) round(array_sum($hrs) / count($hrs));
            $hrMax = max($hrs);
        } else {
            $hrStats = TrackMetrics::heartRateStatsFromCoords($coords);
            $hrAvg = $hrStats['avg'];
            $hrMax = $hrStats['max'];
        }

        $actividad = null;

        DB::transaction(function () use (
            $user,
            $titulo,
            $geojson,
            $distancia,
            $fechaInicio,
            $fechaFin,
            $dur,
            $pace,
            $dplus,
            $hrAvg,
            $hrMax,
            &$actividad,
        ) {
            $now = now();
            $actividad = Actividad::create([
                'user_id' => $user->id,
                'club_id' => null,
                'titulo' => $titulo,
                'descripcion' => null,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'estado' => Actividad::ESTADO_FINALIZADA,
                'modo_creacion' => Actividad::MODO_IMPORTADA,
                'track_geojson' => $geojson,
                'distancia' => $distancia > 0 ? round($distancia, 2) : null,
                'duracion_segundos' => $dur,
                'ritmo_segundos_por_km' => $pace,
                'desnivel_positivo_m' => $dplus,
                'pulsaciones_media' => $hrAvg,
                'pulsaciones_max' => $hrMax,
                'finalizada_at' => $now,
            ]);

            $resumenParts = $this->feedResumenParts($distancia, $dur, $dplus, $pace, $hrAvg);

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

    /**
     * @return list<string>
     */
    private function feedResumenParts(
        float $distanciaKm,
        ?int $duracionSegundos,
        ?int $desnivelM,
        ?int $ritmoSegPorKm,
        ?int $pulsacionesMedia,
    ): array {
        $parts = [];
        if ($distanciaKm > 0) {
            $parts[] = round($distanciaKm, 2).' km';
        }
        if ($duracionSegundos !== null && $duracionSegundos > 0) {
            $parts[] = $this->formatDurationMinutes($duracionSegundos);
        }
        if ($ritmoSegPorKm !== null && $ritmoSegPorKm > 0) {
            $parts[] = $this->formatPace($ritmoSegPorKm).'/km';
        }
        if ($desnivelM !== null) {
            $parts[] = '+'.$desnivelM.' m';
        }
        if ($pulsacionesMedia !== null && $pulsacionesMedia > 0) {
            $parts[] = $pulsacionesMedia.' lpm media';
        }

        return $parts;
    }

    private function formatDurationMinutes(int $seconds): string
    {
        $h = intdiv($seconds, 3600);
        $m = intdiv($seconds % 3600, 60);
        if ($h > 0) {
            return $h.' h '.$m.' min';
        }

        return $m.' min';
    }

    private function formatPace(int $secPerKm): string
    {
        $m = intdiv($secPerKm, 60);
        $s = $secPerKm % 60;

        return sprintf('%d:%02d', $m, $s);
    }

    /**
     * @param  list<float|int|null>  $p
     * @return list<float|int>
     */
    private function normalizeCoordinateRow(array $p): array
    {
        $row = [(float) $p[0], (float) $p[1]];
        if (! array_key_exists(2, $p) || $p[2] === null) {
            return $row;
        }
        if (! array_key_exists(3, $p) || $p[3] === null) {
            $v2 = (float) $p[2];
            if ($v2 > 1_000_000_000) {
                $row[] = (int) $v2;

                return $row;
            }
            $row[] = $v2;

            return $row;
        }
        $row[] = (float) $p[2];
        $row[] = (int) $p[3];
        if (array_key_exists(4, $p) && $p[4] !== null) {
            $row[] = (int) $p[4];
        }

        return $row;
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
}

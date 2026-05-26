<?php

namespace App\Models;

use App\Support\OpenTopoDataElevation;
use App\Support\TrackMetrics;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Actividad extends Model implements HasMedia
{
    use HasFactory, InteractsWithMedia;

    protected $table = 'actividades';

    protected $fillable = [
        'club_id',
        'user_id',
        'titulo',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'lugar',
        'punto_encuentro',
        'material_necesario',
        'modalidad',
        'distancia',
        'desnivel_positivo_m',
        'duracion_segundos',
        'ritmo_segundos_por_km',
        'pulsaciones_media',
        'pulsaciones_max',
        'dificultad',
        'cupo_maximo',
        'costo',
        'estado',
        'modo_creacion',
        'track_geojson',
        'motivo_cancelacion',
        'finalizada_at',
        'publicada_en_feed',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'finalizada_at' => 'datetime',
        'distancia' => 'decimal:2',
        'desnivel_positivo_m' => 'integer',
        'duracion_segundos' => 'integer',
        'ritmo_segundos_por_km' => 'integer',
        'pulsaciones_media' => 'integer',
        'pulsaciones_max' => 'integer',
        'costo' => 'decimal:2',
        'track_geojson' => 'array',
        'publicada_en_feed' => 'boolean',
    ];

    public const ESTADO_PROGRAMADA = 'programada';

    public const ESTADO_EN_CURSO = 'en_curso';

    public const ESTADO_FINALIZADA = 'finalizada';

    public const ESTADO_CANCELADA = 'cancelada';

    public const MODO_VIVO = 'vivo';

    public const MODO_DIBUJADA = 'dibujada';

    public const MODO_IMPORTADA = 'importada';

    public function club(): BelongsTo
    {
        return $this->belongsTo(Club::class);
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function inscripciones(): HasMany
    {
        return $this->hasMany(Inscripcion::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'inscripciones')
            ->withPivot('id', 'fecha_inscripcion');
    }

    public function guias(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'actividad_guia')
            ->withTimestamps();
    }

    /**
     * Longitud aproximada en km del recorrido (GeoJSON LineString / MultiLineString / Feature).
     * Coordenadas en [lng, lat].
     */
    public static function distanciaKmDesdeTrackGeoJson(mixed $geo): ?float
    {
        $coords = self::lineStringCoordinatesFromGeoJson($geo);
        if (count($coords) < 2) {
            return null;
        }

        $km = 0.0;
        for ($i = 1, $n = count($coords); $i < $n; $i++) {
            $km += self::haversineKm(
                (float) $coords[$i - 1][0],
                (float) $coords[$i - 1][1],
                (float) $coords[$i][0],
                (float) $coords[$i][1],
            );
        }

        return round($km, 2);
    }

    /**
     * Desnivel positivo (m) a partir de coordenadas con cota opcional (GeoJSON 2D/3D o extensiones usadas en el feed).
     */
    public static function desnivelPositivoMDesdeTrackGeoJson(mixed $geo): ?int
    {
        $raw = self::lineStringRawCoordinatesFromGeoJson($geo);
        if (count($raw) < 2) {
            return null;
        }

        $d = TrackMetrics::positiveElevationGainMFromCoords($raw);

        return $d;
    }

    /**
     * Añade cota (m) a los vértices que solo traen [lng, lat], consultando OpenTopoData (ASTER 30m).
     * Misma base que el feed: {@see TrackMetrics::positiveElevationGainMFromCoords} con Z en cada punto.
     */
    public static function hydrateTrackGeoJsonElevations(mixed $geo): mixed
    {
        if (! is_array($geo)) {
            return $geo;
        }

        $type = $geo['type'] ?? null;

        if ($type === 'Feature' && isset($geo['geometry']) && is_array($geo['geometry'])) {
            $geo['geometry'] = self::hydrateGeometryElevations($geo['geometry']);

            return $geo;
        }

        if ($type === 'FeatureCollection') {
            $features = $geo['features'] ?? [];
            if (is_array($features)) {
                foreach ($features as $k => $feature) {
                    $geo['features'][$k] = self::hydrateTrackGeoJsonElevations($feature);
                }
            }

            return $geo;
        }

        if ($type === 'LineString' || $type === 'MultiLineString') {
            return self::hydrateGeometryElevations($geo);
        }

        return $geo;
    }

    /**
     * @return list<list<float|int>>
     */
    public static function lineStringRawCoordinatesFromGeoJson(mixed $geo): array
    {
        if (! is_array($geo)) {
            return [];
        }

        $type = $geo['type'] ?? null;

        if ($type === 'Feature') {
            return self::lineStringRawCoordinatesFromGeoJson($geo['geometry'] ?? []);
        }

        if ($type === 'FeatureCollection') {
            $out = [];
            foreach ($geo['features'] ?? [] as $feature) {
                foreach (self::lineStringRawCoordinatesFromGeoJson($feature) as $pair) {
                    $out[] = $pair;
                }
            }

            return $out;
        }

        if ($type === 'LineString') {
            $c = $geo['coordinates'] ?? [];

            return is_array($c) ? array_values(array_filter($c, 'is_array')) : [];
        }

        if ($type === 'MultiLineString') {
            $out = [];
            foreach ($geo['coordinates'] ?? [] as $line) {
                if (is_array($line)) {
                    foreach ($line as $pair) {
                        if (is_array($pair) && count($pair) >= 2) {
                            $out[] = $pair;
                        }
                    }
                }
            }

            return $out;
        }

        return [];
    }

    /**
     * @return list<array{0: float|int, 1: float|int}>
     */
    private static function lineStringCoordinatesFromGeoJson(mixed $geo): array
    {
        return self::lngLatPairsFromRaw(self::lineStringRawCoordinatesFromGeoJson($geo));
    }

    /**
     * @param  list<list<float|int>>  $raw
     * @return list<array{0: float, 1: float}>
     */
    private static function lngLatPairsFromRaw(array $raw): array
    {
        $out = [];
        foreach ($raw as $pair) {
            if (count($pair) >= 2) {
                $out[] = [(float) $pair[0], (float) $pair[1]];
            }
        }

        return $out;
    }

    /**
     * @param  array{type?: string, coordinates?: mixed}  $geom
     * @return array{type?: string, coordinates?: mixed}
     */
    private static function hydrateGeometryElevations(array $geom): array
    {
        $type = $geom['type'] ?? null;
        if ($type === 'LineString') {
            $coords = $geom['coordinates'] ?? [];
            if (is_array($coords)) {
                $geom['coordinates'] = self::injectElevationsIntoCoordinateList($coords);
            }

            return $geom;
        }
        if ($type === 'MultiLineString') {
            $lines = $geom['coordinates'] ?? [];
            $newLines = [];
            if (is_array($lines)) {
                foreach ($lines as $line) {
                    $newLines[] = is_array($line)
                        ? self::injectElevationsIntoCoordinateList($line)
                        : $line;
                }
            }
            $geom['coordinates'] = $newLines;

            return $geom;
        }

        return $geom;
    }

    /**
     * @param  list<list<float|int>>  $coords
     * @return list<list<float|int>>
     */
    private static function injectElevationsIntoCoordinateList(array $coords): array
    {
        $needIdx = [];
        $latLngPairs = [];
        foreach ($coords as $i => $c) {
            if (! is_array($c) || count($c) < 2) {
                continue;
            }
            if (self::coordinateHasElevationM($c)) {
                continue;
            }
            $needIdx[] = $i;
            $latLngPairs[] = [(float) $c[1], (float) $c[0]];
        }

        if (count($needIdx) < 2) {
            // Menos de dos vértices sin cota: no tiene sentido calcular desnivel ni llamar a la API.
            return $coords;
        }

        $elevations = OpenTopoDataElevation::elevationsForLatLngPairs($latLngPairs);
        if (count($elevations) !== count($needIdx)) {
            return $coords;
        }

        foreach ($needIdx as $k => $i) {
            $ele = $elevations[$k] ?? null;
            if ($ele === null || ! is_finite($ele)) {
                continue;
            }
            $z = (int) round($ele);
            $c = $coords[$i];
            $n = count($c);
            if ($n === 2) {
                $coords[$i] = [(float) $c[0], (float) $c[1], $z];
            } elseif ($n === 3 && isset($c[2]) && (float) $c[2] > 1_000_000_000) {
                // [lng, lat, timestamp] → [lng, lat, elev_m, timestamp]
                $coords[$i] = [(float) $c[0], (float) $c[1], $z, (int) $c[2]];
            }
        }

        return $coords;
    }

    /**
     * @param  list<float|int>  $c
     */
    private static function coordinateHasElevationM(array $c): bool
    {
        $n = count($c);
        if ($n >= 4 && is_numeric($c[2])) {
            return true;
        }
        // En [lng, lat, z]: z < 1e9 es cota; valores mayores son timestamps del feed en vivo.
        if ($n === 3 && is_numeric($c[2]) && (float) $c[2] < 1_000_000_000) {
            return true;
        }

        return false;
    }

    private static function haversineKm(float $lng1, float $lat1, float $lng2, float $lat2): float
    {
        $r = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1.0, sqrt($a)));
    }
}

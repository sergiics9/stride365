<?php

namespace App\Models;

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
     * @return list<array{0: float|int, 1: float|int}>
     */
    private static function lineStringCoordinatesFromGeoJson(mixed $geo): array
    {
        if (! is_array($geo)) {
            return [];
        }

        $type = $geo['type'] ?? null;

        if ($type === 'Feature') {
            return self::lineStringCoordinatesFromGeoJson($geo['geometry'] ?? []);
        }

        if ($type === 'FeatureCollection') {
            $out = [];
            foreach ($geo['features'] ?? [] as $feature) {
                foreach (self::lineStringCoordinatesFromGeoJson($feature) as $pair) {
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

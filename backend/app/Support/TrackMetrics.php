<?php

namespace App\Support;

final class TrackMetrics
{
    private const ELEVATION_NOISE_M = 3.0;

    private const MIN_DISTANCE_KM_FOR_PACE = 0.05;

    /**
     * @param  list<list<float|int>>  $coords  [lng, lat] | [lng, lat, ts] | [lng, lat, ele, ts] | [lng, lat, ele, ts, hr]
     */
    public static function durationSecondsFromCoords(array $coords): ?int
    {
        $first = null;
        $last = null;
        foreach ($coords as $c) {
            if (! is_array($c)) {
                continue;
            }
            $ts = self::timestampFromCoord($c);
            if ($ts === null) {
                continue;
            }
            if ($first === null) {
                $first = $ts;
            }
            $last = $ts;
        }

        if ($first === null || $last === null || $last <= $first) {
            return null;
        }

        return $last - $first;
    }

    /**
     * @param  list<list<float|int>>  $coords
     * @return list<float>
     */
    public static function elevationsFromCoords(array $coords): array
    {
        $out = [];
        foreach ($coords as $c) {
            if (! is_array($c)) {
                continue;
            }
            $n = count($c);
            if ($n >= 4 && is_numeric($c[2])) {
                $out[] = (float) $c[2];

                continue;
            }
            if ($n === 3 && is_numeric($c[2]) && (float) $c[2] < 1_000_000_000) {
                $out[] = (float) $c[2];
            }
        }

        return $out;
    }

    /**
     * @param  list<list<float|int>>  $coords
     */
    public static function positiveElevationGainMFromCoords(array $coords): ?int
    {
        $series = self::elevationsFromCoords($coords);
        if (count($series) < 2) {
            return null;
        }

        return self::positiveElevationGainM($series, self::ELEVATION_NOISE_M);
    }

    /**
     * @param  list<float>  $elevationsM
     */
    public static function positiveElevationGainM(array $elevationsM, float $noiseThresholdM = 3.0): int
    {
        if (count($elevationsM) < 2) {
            return 0;
        }

        // Cumulative approach: compare each point to the last confirmed reference,
        // not just the previous point. This correctly handles smooth/high-frequency
        // tracks where consecutive step differences are below the noise threshold but
        // the cumulative ascent is real (comparing consecutive points would yield 0).
        $gain = 0.0;
        $ref = $elevationsM[0];

        for ($i = 1, $n = count($elevationsM); $i < $n; $i++) {
            $delta = $elevationsM[$i] - $ref;
            if ($delta >= $noiseThresholdM) {
                $gain += $delta;
                $ref = $elevationsM[$i];
            } elseif ($delta <= -$noiseThresholdM) {
                // Confirmed descent — reset reference so the next ascent is measured fresh.
                $ref = $elevationsM[$i];
            }
        }

        return (int) round($gain);
    }

    /**
     * @param  list<list<float|int>>  $coords
     * @return array{avg: ?int, max: ?int}
     */
    public static function heartRateStatsFromCoords(array $coords): array
    {
        $hrs = [];
        foreach ($coords as $c) {
            if (! is_array($c) || count($c) < 5 || ! is_numeric($c[4])) {
                continue;
            }
            $v = (int) $c[4];
            if ($v > 0) {
                $hrs[] = $v;
            }
        }
        if ($hrs === []) {
            return ['avg' => null, 'max' => null];
        }

        return [
            'avg' => (int) round(array_sum($hrs) / count($hrs)),
            'max' => max($hrs),
        ];
    }

    /**
     * @param  list<list<float|int>>  $coords
     */
    public static function paceSecondsPerKm(?int $durationSeconds, float $distanceKm): ?int
    {
        if ($durationSeconds === null || $durationSeconds <= 0) {
            return null;
        }
        if ($distanceKm < self::MIN_DISTANCE_KM_FOR_PACE) {
            return null;
        }

        return (int) round($durationSeconds / $distanceKm);
    }

    /**
     * @param  list<float|int>  $c
     */
    public static function timestampFromCoord(array $c): ?int
    {
        return match (count($c)) {
            3 => is_numeric($c[2]) && (float) $c[2] > 1_000_000_000 ? (int) $c[2] : null,
            4, 5 => is_numeric($c[3]) ? (int) $c[3] : null,
            default => null,
        };
    }
}

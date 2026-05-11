<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Cotas aproximadas (m) con el dataset ASTER 30m de OpenTopoData.
 * Útil para recorridos dibujados en mapa sin altitud (el feed ya envía Z desde el GPS).
 *
 * @see https://api.opentopodata.org/
 */
final class OpenTopoDataElevation
{
    public const MAX_LOCATIONS_PER_REQUEST = 100;

    /**
     * @param  list<array{0: float, 1: float}>  $latLngPairs  [lat, lng]
     * @return list<float|null> Misma longitud que la entrada
     */
    public static function elevationsForLatLngPairs(array $latLngPairs): array
    {
        if ($latLngPairs === []) {
            return [];
        }

        $out = [];
        foreach (array_chunk($latLngPairs, self::MAX_LOCATIONS_PER_REQUEST) as $chunkIndex => $chunk) {
            if ($chunkIndex > 0) {
                usleep(1_100_000);
            }

            $locations = implode('|', array_map(
                static fn (array $p) => $p[0].','.$p[1],
                $chunk
            ));

            try {
                $response = Http::timeout(25)
                    ->acceptJson()
                    ->get('https://api.opentopodata.org/v1/aster30m', [
                        'locations' => $locations,
                    ]);

                if (! $response->successful()) {
                    $out = array_merge($out, array_fill(0, count($chunk), null));

                    continue;
                }

                $data = $response->json();
                $results = $data['results'] ?? [];
                foreach ($results as $r) {
                    $e = $r['elevation'] ?? null;
                    $out[] = is_numeric($e) ? (float) $e : null;
                }
                $missing = count($chunk) - count($results);
                if ($missing > 0) {
                    $out = array_merge($out, array_fill(0, $missing, null));
                }
            } catch (Throwable $e) {
                Log::debug('OpenTopoData elevation request failed', [
                    'message' => $e->getMessage(),
                ]);
                $out = array_merge($out, array_fill(0, count($chunk), null));
            }
        }

        return $out;
    }
}

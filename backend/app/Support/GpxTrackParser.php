<?php

namespace App\Support;

use Carbon\Carbon;
use DOMDocument;
use DOMElement;
use DOMXPath;

final class GpxTrackParser
{
    /**
     * @return array{
     *     geojson: array{type: string, coordinates: list<list<float|int>>},
     *     started_at: ?Carbon,
     *     finished_at: ?Carbon,
     *     heart_rates: list<int>
     * }
     */
    public static function parse(string $xml): array
    {
        $dom = new DOMDocument;
        if (! @$dom->loadXML($xml)) {
            throw new \InvalidArgumentException('XML inválido');
        }

        $xp = new DOMXPath($dom);
        // local-name() ignora namespaces (gpx:, etc.) y localiza trkpt en cualquier GPX válido.
        $nodes = $xp->query('//*[local-name()="trkpt"]');
        if ($nodes === false || $nodes->length < 2) {
            throw new \InvalidArgumentException('No se encontraron puntos trkpt');
        }

        $coordinates = [];
        $heartRates = [];
        $startedAt = null;
        $finishedAt = null;

        for ($i = 0; $i < $nodes->length; $i++) {
            $node = $nodes->item($i);
            if (! ($node instanceof DOMElement)) {
                continue;
            }
            $lat = (float) $node->getAttribute('lat');
            $lon = (float) $node->getAttribute('lon');

            $ele = self::firstChildFloatLocal($node, 'ele');
            $timeStr = self::firstChildTextLocal($node, 'time');
            $hr = self::firstHeartRateBpm($node, $xp);

            $ts = null;
            if ($timeStr !== null) {
                try {
                    $t = Carbon::parse($timeStr);
                    $ts = $t->getTimestamp();
                    $startedAt ??= $t;
                    $finishedAt = $t;
                } catch (\Throwable) {
                    $ts = null;
                }
            }

            // GeoJSON interno: [lng, lat] | [lng, lat, ts] | [lng, lat, ele] | [lng, lat, ele, ts] | + FC
            // GPX de Strava sin <time> solo aporta elevación → sin duración ni ritmo al importar.
            $row = [$lon, $lat];
            if ($ele !== null && $ts !== null) {
                $row[] = round($ele, 1);
                $row[] = $ts;
                if ($hr !== null) {
                    $row[] = $hr;
                    $heartRates[] = $hr;
                }
            } elseif ($ts !== null) {
                $row[] = $ts;
            } elseif ($ele !== null) {
                $row[] = round($ele, 1);
            }

            $coordinates[] = $row;
        }

        if (count($coordinates) < 2) {
            throw new \InvalidArgumentException('No se encontraron puntos trkpt');
        }

        return [
            'geojson' => [
                'type' => 'LineString',
                'coordinates' => $coordinates,
            ],
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
            'heart_rates' => $heartRates,
        ];
    }

    private static function firstChildFloatLocal(DOMElement $el, string $localName): ?float
    {
        foreach ($el->childNodes as $child) {
            if ($child instanceof DOMElement && strcasecmp($child->localName, $localName) === 0) {
                $v = trim($child->textContent);

                return is_numeric($v) ? (float) $v : null;
            }
        }

        return null;
    }

    private static function firstChildTextLocal(DOMElement $el, string $localName): ?string
    {
        foreach ($el->childNodes as $child) {
            if ($child instanceof DOMElement && strcasecmp($child->localName, $localName) === 0) {
                $v = trim($child->textContent);

                return $v !== '' ? $v : null;
            }
        }

        return null;
    }

    private static function firstHeartRateBpm(DOMElement $trkpt, DOMXPath $xp): ?int
    {
        $list = $xp->query('.//*[local-name()="hr"]', $trkpt);
        if ($list === false || $list->length === 0) {
            return null;
        }
        $first = $list->item(0);
        if (! $first) {
            return null;
        }
        $v = trim($first->textContent);
        if (! is_numeric($v)) {
            return null;
        }
        $n = (int) $v;

        return $n > 0 ? $n : null;
    }
}

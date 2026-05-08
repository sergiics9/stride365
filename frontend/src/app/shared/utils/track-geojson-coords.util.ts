/** Extrae pares [lng, lat] de un GeoJSON de track (Feature, LineString, MultiLineString, FeatureCollection). */
export function extractLngLatPairsFromTrackGeoJson(geo: unknown): [number, number][] {
  const out: [number, number][] = [];

  const walk = (g: unknown): void => {
    if (!g || typeof g !== 'object') {
      return;
    }
    const obj = g as {
      type?: string;
      coordinates?: unknown;
      geometry?: unknown;
      features?: unknown[];
    };
    if (obj.type === 'LineString' && Array.isArray(obj.coordinates)) {
      for (const c of obj.coordinates) {
        if (Array.isArray(c) && c.length >= 2) {
          const lng = Number(c[0]);
          const lat = Number(c[1]);
          if (Number.isFinite(lng) && Number.isFinite(lat)) {
            out.push([lng, lat]);
          }
        }
      }
      return;
    }
    if (obj.type === 'MultiLineString' && Array.isArray(obj.coordinates)) {
      for (const line of obj.coordinates as unknown[][]) {
        if (!Array.isArray(line)) {
          continue;
        }
        for (const c of line) {
          if (Array.isArray(c) && c.length >= 2) {
            const lng = Number(c[0]);
            const lat = Number(c[1]);
            if (Number.isFinite(lng) && Number.isFinite(lat)) {
              out.push([lng, lat]);
            }
          }
        }
      }
      return;
    }
    if (obj.type === 'Feature' && obj.geometry) {
      walk(obj.geometry);
      return;
    }
    if (obj.type === 'FeatureCollection' && Array.isArray(obj.features)) {
      for (const f of obj.features) {
        walk(f);
      }
    }
  };

  walk(geo);
  return out;
}

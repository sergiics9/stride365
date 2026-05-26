
// Nuestros tracks pueden traer cotas, timestamps o pulsaciones en cada punto.
// Leaflet solo necesita [lng, lat] para dibujar, así que recortamos el resto.
export function stripGeoJsonCoordinatesTo2D(geo: unknown): unknown {
  const stripRing = (ring: unknown): number[][] => {
    if (!Array.isArray(ring)) return [];
    return ring.map((c) => {
      if (Array.isArray(c) && c.length >= 2 && typeof c[0] === 'number' && typeof c[1] === 'number') {
        return [c[0], c[1]];
      }
      return c as number[];
    });
  };

  const walk = (g: unknown): unknown => {
    if (!g || typeof g !== 'object') {
      return g;
    }
    const obj = g as {
      type?: string;
      coordinates?: unknown;
      geometry?: unknown;
      features?: unknown[];
    };
    if (obj.type === 'LineString' && Array.isArray(obj.coordinates)) {
      return { ...obj, coordinates: stripRing(obj.coordinates) };
    }
    if (obj.type === 'MultiLineString' && Array.isArray(obj.coordinates)) {
      return {
        ...obj,
        coordinates: (obj.coordinates as unknown[][]).map((line) => stripRing(line)),
      };
    }
    if (obj.type === 'Feature' && obj.geometry) {
      return { ...obj, geometry: walk(obj.geometry) };
    }
    if (obj.type === 'FeatureCollection' && Array.isArray(obj.features)) {
      return {
        ...obj,
        features: obj.features.map((f) => walk(f)),
      };
    }
    return g;
  };

  return walk(geo);
}

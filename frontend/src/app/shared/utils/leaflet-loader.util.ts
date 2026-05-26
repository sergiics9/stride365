import type * as LType from 'leaflet';

// Leaflet se carga bajo demanda para no inflar el bundle inicial de la app.
// Al importarlo dinámicamente, el bundler puede devolver el módulo en `.default`.
export async function loadLeaflet(): Promise<typeof LType> {
  const mod = (await import('leaflet')) as unknown as Record<string, unknown>;
  return (mod['default'] ?? mod) as unknown as typeof LType;
}

import type * as LType from 'leaflet';

export async function loadLeaflet(): Promise<typeof LType> {
  const mod = (await import('leaflet')) as unknown as Record<string, unknown>;
  return (mod['default'] ?? mod) as unknown as typeof LType;
}

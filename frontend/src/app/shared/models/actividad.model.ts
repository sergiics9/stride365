import { MediaItem } from './media.model';

export type ActividadEstado = 'programada' | 'en_curso' | 'finalizada' | 'cancelada';
export type Dificultad = 'facil' | 'media' | 'dificil' | 'extrema';
export type ModoCreacion = 'vivo' | 'dibujada' | 'importada';

export interface ActividadGuia {
  id: number;
  nombre: string | null;
  apellido: string | null;
  email: string;
}

export interface Actividad {
  id: number;
  club_id: number | null;
  user_id?: number | null;
  titulo: string;
  descripcion: string | null;
  fecha_inicio: string;
  fecha_fin: string | null;
  lugar: string | null;
  punto_encuentro: string | null;
  material_necesario: string | null;
  modalidad: string | null;
  distancia: string | number | null;
  desnivel_positivo_m?: number | null;
  duracion_segundos?: number | null;
  ritmo_segundos_por_km?: number | null;
  pulsaciones_media?: number | null;
  pulsaciones_max?: number | null;
  dificultad: Dificultad | null;
  cupo_maximo: number | null;
  costo: string | number | null;
  estado: ActividadEstado;
  modo_creacion: ModoCreacion;
  track_geojson: GeoJSON.GeoJSON | null;
  motivo_cancelacion: string | null;
  finalizada_at: string | null;
  publicada_en_feed: boolean;
  inscripciones_count?: number;
  guias?: ActividadGuia[];
  media?: MediaItem[];
  created_at?: string;
  updated_at?: string;
}

export interface CreateActividadRequest {
  titulo: string;
  descripcion?: string | null;
  fecha_inicio: string;
  fecha_fin?: string | null;
  lugar?: string | null;
  punto_encuentro?: string | null;
  material_necesario?: string | null;
  modalidad?: string | null;
  distancia?: number | null;
  dificultad?: Dificultad | null;
  cupo_maximo?: number | null;
  costo?: number | null;
  estado?: ActividadEstado;
  modo_creacion?: ModoCreacion;
  track_geojson?: GeoJSON.GeoJSON | null;
  guia_ids?: number[];
}

export type UpdateActividadRequest = Partial<CreateActividadRequest> & {
  motivo_cancelacion?: string | null;
};

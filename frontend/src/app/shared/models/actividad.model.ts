import { MediaItem } from './media.model';

export type EstadoActividad = 'programada' | 'en_curso' | 'finalizada' | 'cancelada';
export type Dificultad = 'baja' | 'media' | 'alta';

export interface Actividad {
  id: number;
  club_id: number;
  titulo: string;
  descripcion: string | null;
  fecha_inicio: string;
  fecha_fin: string | null;
  lugar: string | null;
  modalidad: string | null;
  distancia: string | null;
  dificultad: Dificultad | null;
  cupo_maximo: number | null;
  costo: string | null;
  estado: EstadoActividad;
  created_at?: string;
  updated_at?: string;
  inscripciones_count?: number;
  media?: MediaItem[];
}

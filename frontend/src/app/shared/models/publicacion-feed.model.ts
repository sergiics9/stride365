import { Actividad } from './actividad.model';
import { ClubSummary } from './club.model';
import { MediaItem } from './media.model';
import { User } from './user.model';

export type EstadoPublicacion = 'activo' | 'oculto' | 'eliminado';

export interface PublicacionFeed {
  id: number;
  user_id: number;
  actividad_id: number | null;
  titulo: string | null;
  resumen: string | null;
  contenido: string | null;
  imagen_url: string | null;
  tipo: string | null;
  visibilidad: string | null;
  fecha_publicacion: string;
  estado: EstadoPublicacion;
  created_at?: string;
  updated_at?: string;
  user?: Pick<User, 'id' | 'nombre' | 'apellido' | 'email'>;
  actividad?: (Pick<
    Actividad,
    | 'id'
    | 'club_id'
    | 'user_id'
    | 'titulo'
    | 'fecha_inicio'
    | 'fecha_fin'
    | 'distancia'
    | 'desnivel_positivo_m'
    | 'duracion_segundos'
    | 'ritmo_segundos_por_km'
    | 'pulsaciones_media'
    | 'pulsaciones_max'
    | 'dificultad'
    | 'modalidad'
    | 'track_geojson'
    | 'modo_creacion'
  > & {
    club?: ClubSummary | null;
  }) | null;
  media?: MediaItem[];
}

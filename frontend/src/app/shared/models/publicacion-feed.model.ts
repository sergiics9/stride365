import { MediaItem } from './media.model';
import { User } from './user.model';

export type EstadoPublicacion = 'activo' | 'oculto' | 'eliminado';

export interface PublicacionFeed {
  id: number;
  user_id: number;
  titulo: string | null;
  contenido: string;
  imagen_url: string | null;
  tipo: string | null;
  visibilidad: string | null;
  fecha_publicacion: string;
  estado: EstadoPublicacion;
  created_at?: string;
  updated_at?: string;
  user?: Pick<User, 'id' | 'nombre' | 'apellido' | 'email'>;
  media?: MediaItem[];
}

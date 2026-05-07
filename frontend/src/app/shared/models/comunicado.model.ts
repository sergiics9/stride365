export interface ComunicadoUser {
  id: number;
  nombre: string | null;
  apellido: string | null;
  email: string;
}

export interface Comunicado {
  id: number;
  club_id: number;
  user_id: number | null;
  titulo: string;
  contenido: string;
  fecha_publicacion: string;
  user?: ComunicadoUser;
  created_at?: string;
  updated_at?: string;
}

export interface CreateComunicadoRequest {
  titulo: string;
  contenido: string;
  fecha_publicacion?: string | null;
}

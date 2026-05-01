export interface Comunicado {
  id: number;
  grupo_id: number;
  user_id: number | null;
  titulo: string;
  contenido: string;
  fecha_publicacion: string;
  created_at?: string;
  updated_at?: string;
}

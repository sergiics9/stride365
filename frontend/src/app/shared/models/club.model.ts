export interface Club {
  id: number;
  nombre: string;
  descripcion: string | null;
  direccion: string | null;
  telefono: string | null;
  email: string | null;
  created_at?: string;
  updated_at?: string;
  users_count?: number;
  grupos_count?: number;
  actividades_count?: number;
}

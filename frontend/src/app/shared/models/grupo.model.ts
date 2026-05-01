export interface Grupo {
  id: number;
  club_id: number;
  nombre: string;
  descripcion: string | null;
  created_at?: string;
  updated_at?: string;
}

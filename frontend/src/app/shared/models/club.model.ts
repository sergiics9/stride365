export type ClubApplicationStatus = 'pending' | 'approved' | 'rejected';

export interface Club {
  id: number;
  nombre: string;
  slug: string | null;
  descripcion: string | null;
  logo_url: string | null;
  direccion: string | null;
  telefono: string | null;
  email: string | null;
  active: boolean;
  application_status: ClubApplicationStatus;
  requested_by?: number | null;
  approved_by?: number | null;
  approved_at?: string | null;
  rejection_reason?: string | null;
  created_at?: string;
  updated_at?: string;
  socios_count?: number;
  comunicados_count?: number;
  actividades_count?: number;
}

export interface ClubSummary {
  id: number;
  nombre: string;
  slug: string | null;
  logo_url: string | null;
  active: boolean;
  application_status: ClubApplicationStatus;
}

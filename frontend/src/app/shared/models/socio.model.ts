import { MembershipStatus } from './membership.model';

export interface Socio {
  id: number;
  club_id: number;
  user_id: number;
  user: {
    id: number;
    nombre: string | null;
    apellido: string | null;
    email: string;
    telefono: string | null;
    foto_url: string | null;
    fecha_alta: string | null;
    estado: string;
    direccion?: string | null;
    fecha_nacimiento?: string | null;
    sexo?: string | null;
  } | null;
  role: 'socio';
  is_guide: boolean;
  status: MembershipStatus;
  subscription_name: string | null;
  current_period_end: string | null;
  ends_at: string | null;
  joined_at: string | null;
  left_at: string | null;
  left_reason: string | null;
}

export interface CreateSocioRequest {
  email: string;
  nombre?: string | null;
  apellido?: string | null;
  telefono?: string | null;
  password?: string | null;
  status?: MembershipStatus;
}

export interface UpdateSocioRequest {
  nombre?: string | null;
  apellido?: string | null;
  telefono?: string | null;
  direccion?: string | null;
  fecha_nacimiento?: string | null;
  sexo?: string | null;
  is_guide?: boolean;
  status?: MembershipStatus;
}

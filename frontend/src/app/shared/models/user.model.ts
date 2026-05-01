import { Club } from './club.model';
import { Role } from './role.model';

export type EstadoUser = 'activo' | 'baja' | 'suspendido';
export type Sexo = 'M' | 'F' | 'O';

export interface User {
  id: number;
  club_id: number | null;
  nombre: string | null;
  apellido: string | null;
  fecha_nacimiento: string | null;
  sexo: Sexo | null;
  telefono: string | null;
  email: string;
  foto_url: string | null;
  direccion: string | null;
  fecha_alta: string | null;
  estado: EstadoUser;
  email_verified_at: string | null;
  created_at?: string;
  updated_at?: string;
  roles?: Role[];
  club?: Club | null;
}

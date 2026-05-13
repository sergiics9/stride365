import { Membership } from './membership.model';
import { RoleName } from './role.model';
import { User } from './user.model';

export interface LoginRequest {
  email: string;
  password: string;
  device_name?: string;
}

export interface RegisterRequest {
  nombre: string;
  apellido: string;
  email: string;
  telefono?: string | null;
  sexo?: 'M' | 'F' | 'O' | null;
  fecha_nacimiento?: string | null;
  direccion?: string | null;
  password: string;
  password_confirmation: string;
  device_name?: string;
  foto?: File | null;
}

export interface UpdateProfilePayload {
  foto?: File | null;
}

export interface AuthResponse {
  token: string;
  token_type: 'Bearer';
  user: User;
}

export interface MeResponse {
  user: User;
  roles: RoleName[];
  memberships: Membership[];
}

export interface ForgotPasswordRequest {
  email: string;
}

export interface ResetPasswordRequest {
  token: string;
  email: string;
  password: string;
  password_confirmation: string;
}

export interface MessageResponse {
  message: string;
}

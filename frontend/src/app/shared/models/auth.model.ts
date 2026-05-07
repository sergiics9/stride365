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

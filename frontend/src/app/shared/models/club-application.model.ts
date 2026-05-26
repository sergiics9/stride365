import { Club, ClubApplicationStatus } from './club.model';

export interface ClubApplication extends Club {
  application_status: ClubApplicationStatus;
  requester?: {
    id: number;
    nombre: string | null;
    apellido: string | null;
    email: string;
  };
}

export interface CreateClubApplicationPayload {
  nombre: string;
  descripcion?: string | null;
  
  logo?: File | null;
  direccion?: string | null;
  telefono?: string | null;
  email?: string | null;
}

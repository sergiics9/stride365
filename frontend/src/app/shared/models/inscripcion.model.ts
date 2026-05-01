import { Actividad } from './actividad.model';
import { User } from './user.model';

export interface Inscripcion {
  id: number;
  user_id: number;
  actividad_id: number;
  fecha_inscripcion: string;
  user?: Pick<User, 'id' | 'nombre' | 'apellido' | 'email' | 'telefono'>;
  actividad?: Actividad;
}

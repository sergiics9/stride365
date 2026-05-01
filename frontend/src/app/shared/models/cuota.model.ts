export type EstadoCuota = 'pendiente' | 'pagada' | 'vencida' | 'anulada';

export interface Cuota {
  id: number;
  user_id: number;
  periodo: string | null;
  concepto: string | null;
  monto: string;
  fecha_vencimiento: string;
  estado: EstadoCuota;
  created_at?: string;
  updated_at?: string;
}

export type EstadoPago = 'confirmado' | 'pendiente' | 'rechazado';

export interface Pago {
  id: number;
  cuota_id: number;
  fecha_pago: string;
  monto_pagado: string;
  metodo_pago: string | null;
  referencia: string | null;
  estado: EstadoPago;
  observaciones: string | null;
  created_at?: string;
  updated_at?: string;
}

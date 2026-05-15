import { Injectable } from '@angular/core';
import Swal, { SweetAlertIcon } from 'sweetalert2';

export type ToastKind = 'success' | 'error' | 'warning' | 'info';

const SwalToast = Swal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timerProgressBar: true,
  didOpen: (el) => {
    el.onmouseenter = Swal.stopTimer;
    el.onmouseleave = Swal.resumeTimer;
  },
});

@Injectable({ providedIn: 'root' })
export class ToastService {
  show(message: string, kind: ToastKind = 'info', durationMs = 4500): void {
    void SwalToast.fire({ icon: kind as SweetAlertIcon, title: message, timer: durationMs });
  }

  success(message: string, durationMs = 4500): void {
    this.show(message, 'success', durationMs);
  }

  error(message: string, durationMs = 7000): void {
    this.show(message, 'error', durationMs);
  }

  warning(message: string, durationMs = 4500): void {
    this.show(message, 'warning', durationMs);
  }

  info(message: string, durationMs = 4500): void {
    this.show(message, 'info', durationMs);
  }

  /** Mantenido por compatibilidad; Swal gestiona sus propios timers */
  dismiss(_id?: string): void {
    Swal.close();
  }
}

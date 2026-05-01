import { Injectable, signal } from '@angular/core';

export type ToastKind = 'success' | 'error' | 'warning' | 'info';

export interface Toast {
  id: string;
  kind: ToastKind;
  message: string;
}

@Injectable({ providedIn: 'root' })
export class ToastService {
  private readonly _toasts = signal<Toast[]>([]);
  readonly toasts = this._toasts.asReadonly();

  show(message: string, kind: ToastKind = 'info', durationMs = 4500): string {
    const id = crypto.randomUUID();
    this._toasts.update((list) => [...list, { id, kind, message }]);
    if (durationMs > 0) {
      setTimeout(() => this.dismiss(id), durationMs);
    }
    return id;
  }

  success(message: string, durationMs?: number): string {
    return this.show(message, 'success', durationMs);
  }
  error(message: string, durationMs = 7000): string {
    return this.show(message, 'error', durationMs);
  }
  warning(message: string, durationMs?: number): string {
    return this.show(message, 'warning', durationMs);
  }
  info(message: string, durationMs?: number): string {
    return this.show(message, 'info', durationMs);
  }

  dismiss(id: string): void {
    this._toasts.update((list) => list.filter((t) => t.id !== id));
  }
}

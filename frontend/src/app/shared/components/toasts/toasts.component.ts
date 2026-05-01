import { ChangeDetectionStrategy, Component, inject } from '@angular/core';

import { ToastKind, ToastService } from '../../../core/toast/toast.service';

@Component({
  selector: 'app-toasts',
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './toasts.component.html',
  styleUrl: './toasts.component.scss',
})
export class ToastsComponent {
  protected readonly toastService = inject(ToastService);

  protected readonly classMap: Record<ToastKind, string> = {
    success: 'text-bg-success',
    error: 'text-bg-danger',
    warning: 'text-bg-warning',
    info: 'text-bg-primary',
  };

  protected readonly iconMap: Record<ToastKind, string> = {
    success: '✓',
    error: '!',
    warning: '!',
    info: 'i',
  };

  protected dismiss(id: string): void {
    this.toastService.dismiss(id);
  }
}

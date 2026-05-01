import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, inject, input } from '@angular/core';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../../core/auth/auth.service';
import { SubscriptionService } from '../subscription.service';

@Component({
  selector: 'app-subscription-overview',
  imports: [DatePipe, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './subscription-overview.component.html',
  styleUrl: './subscription-overview.component.scss',
})
export class SubscriptionOverviewComponent {
  protected readonly auth = inject(AuthService);
  protected readonly subscription = inject(SubscriptionService);

  readonly reason = input<string | null>(null);

  protected readonly status = this.subscription.status;
  protected readonly loading = this.subscription.loading;
  protected readonly error = this.subscription.error;

  protected readonly badge = computed(() => {
    const s = this.status();
    if (!s) return { label: 'Sin datos', cls: 'bg-secondary' };
    if (s.subscribed && !s.cancelled) return { label: 'Activa', cls: 'bg-success' };
    if (s.on_grace_period) return { label: 'En periodo de gracia', cls: 'bg-warning text-dark' };
    if (s.on_trial) return { label: 'Periodo de prueba', cls: 'bg-info text-dark' };
    if (s.cancelled) return { label: 'Cancelada', cls: 'bg-danger' };
    return { label: 'Sin suscripción', cls: 'bg-secondary' };
  });

  protected readonly notice = computed(() =>
    this.reason() === 'required'
      ? 'Necesitas una suscripción activa para acceder al módulo Clubes.'
      : null,
  );

  protected reload(): void {
    void this.subscription.loadStatus(true);
  }
}

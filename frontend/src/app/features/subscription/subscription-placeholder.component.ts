import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-subscription-placeholder',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="container">
      <h1 class="h3 mb-3">Suscripción</h1>
      <p class="text-muted">Próximamente: gestión de suscripción Stripe (Fase 5).</p>
    </div>
  `,
})
export class SubscriptionPlaceholderComponent {}

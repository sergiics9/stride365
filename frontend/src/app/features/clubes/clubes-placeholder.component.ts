import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-clubes-placeholder',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="container">
      <h1 class="h3 mb-3">Clubes</h1>
      <p class="text-muted">Próximamente: gestión de clubes, socios, actividades (Fase 6+).</p>
    </div>
  `,
})
export class ClubesPlaceholderComponent {}

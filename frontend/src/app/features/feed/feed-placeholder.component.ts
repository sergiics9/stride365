import { ChangeDetectionStrategy, Component } from '@angular/core';

@Component({
  selector: 'app-feed-placeholder',
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="container">
      <h1 class="h3 mb-3">Feed</h1>
      <p class="text-muted">Próximamente: publicaciones de actividades finalizadas (Fase 4).</p>
    </div>
  `,
})
export class FeedPlaceholderComponent {}

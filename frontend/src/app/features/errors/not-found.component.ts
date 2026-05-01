import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-not-found',
  imports: [RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="text-center py-5">
      <h1 class="display-4">404</h1>
      <p class="lead">La página que buscas no existe.</p>
      <a class="btn btn-primary" routerLink="/feed">Volver al inicio</a>
    </div>
  `,
})
export class NotFoundComponent {}

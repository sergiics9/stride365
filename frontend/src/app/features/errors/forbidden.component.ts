import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-forbidden',
  imports: [RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="text-center py-5">
      <h1 class="display-4">403</h1>
      <p class="lead">No tienes permisos para acceder a esta sección.</p>
      <a class="btn btn-primary" routerLink="/feed">Volver al inicio</a>
    </div>
  `,
})
export class ForbiddenComponent {}

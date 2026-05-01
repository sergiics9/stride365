import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterLink } from '@angular/router';

@Component({
  selector: 'app-auth-placeholder',
  imports: [RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="card mx-auto shadow-sm" style="max-width: 420px">
      <div class="card-body p-4">
        <h1 class="h4 mb-3">Acceso</h1>
        <p class="text-muted small mb-3">
          Próximamente: formularios de login y registro con Reactive Forms (Fase 2).
        </p>
        <a class="btn btn-primary w-100" routerLink="/feed">Continuar al Feed</a>
      </div>
    </div>
  `,
})
export class AuthPlaceholderComponent {}

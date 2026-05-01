import { ChangeDetectionStrategy, Component } from '@angular/core';
import { RouterOutlet } from '@angular/router';

@Component({
  selector: 'app-public-layout',
  imports: [RouterOutlet],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div class="public-layout d-flex align-items-center justify-content-center min-vh-100 bg-light">
      <div class="container py-4">
        <router-outlet />
      </div>
    </div>
  `,
  styles: [
    `
      .public-layout {
        background: linear-gradient(160deg, #eef2f7 0%, #f6f8fa 100%);
      }
    `,
  ],
})
export class PublicLayoutComponent {}

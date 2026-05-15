import { ChangeDetectionStrategy, Component, computed, inject } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

import { AuthService } from '../../core/auth/auth.service';
import { ThemeService } from '../../core/theme/theme.service';

@Component({
  selector: 'app-app-layout',
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './app-layout.component.html',
  styleUrl: './app-layout.component.scss',
})
export class AppLayoutComponent {
  protected readonly auth = inject(AuthService);
  protected readonly theme = inject(ThemeService);
  private readonly router = inject(Router);

  protected readonly primaryRoleLabel = computed(() => {
    if (this.auth.isSuperAdmin()) return 'Super admin';

    const admin = this.auth.adminMembership();
    if (admin) {
      return admin.club?.nombre ? `Admin · ${admin.club.nombre}` : 'Admin de club';
    }

    const socios = this.auth.socioMemberships();
    if (socios.length === 1) {
      return socios[0].is_guide ? 'Guía · ' + (socios[0].club?.nombre ?? '') : 'Socio · ' + (socios[0].club?.nombre ?? '');
    }
    if (socios.length > 1) return `Socio en ${socios.length} clubes`;

    return 'Usuario';
  });

  protected async logout(): Promise<void> {
    await this.auth.logout();
    void this.router.navigate(['/auth/login']);
  }
}

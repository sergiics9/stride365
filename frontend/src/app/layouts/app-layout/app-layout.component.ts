import { ChangeDetectionStrategy, Component, computed, inject } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

import { AuthService } from '../../core/auth/auth.service';
import { RoleName } from '../../shared/models';

const ROLE_LABELS: Record<RoleName, string> = {
  super_admin: 'Super admin',
  admin_club: 'Admin club',
  guia: 'Guía',
  socio: 'Socio',
};

@Component({
  selector: 'app-app-layout',
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './app-layout.component.html',
  styleUrl: './app-layout.component.scss',
})
export class AppLayoutComponent {
  protected readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly canAccessClubes = computed(
    () => this.auth.isSuperAdmin() || this.auth.subscribed(),
  );

  protected readonly primaryRoleLabel = computed(() => {
    const roles = this.auth.roles();
    if (!roles.length) return null;
    return ROLE_LABELS[roles[0]] ?? roles[0];
  });

  protected async logout(): Promise<void> {
    await this.auth.logout();
    void this.router.navigate(['/auth/login']);
  }
}

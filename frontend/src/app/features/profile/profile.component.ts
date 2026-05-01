import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, inject } from '@angular/core';
import { RouterLink } from '@angular/router';

import { AuthService } from '../../core/auth/auth.service';

@Component({
  selector: 'app-profile',
  imports: [DatePipe, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './profile.component.html',
  styleUrl: './profile.component.scss',
})
export class ProfileComponent {
  protected readonly auth = inject(AuthService);

  protected readonly user = this.auth.user;
  protected readonly roles = this.auth.roles;

  protected readonly initials = computed(() => {
    const u = this.user();
    if (!u) return '?';
    const a = (u.nombre ?? '').charAt(0);
    const b = (u.apellido ?? '').charAt(0);
    return (a + b).toUpperCase() || u.email.charAt(0).toUpperCase();
  });

  protected readonly roleLabels: Partial<Record<string, string>> = {
    super_admin: 'Super administrador',
    admin_club: 'Administrador de club',
    guia: 'Guía',
    socio: 'Socio',
  };

  protected reload(): void {
    void this.auth.me();
  }
}

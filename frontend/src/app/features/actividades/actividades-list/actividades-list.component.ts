import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../core/auth/auth.service';
import { Actividad, ActividadEstado, Club } from '../../../shared/models';
import { resolvedClub$, findResolvedClub } from '../../../shared/utils/resolved-club-from-route.util';
import { ActividadesService } from '../actividades.service';

@Component({
  selector: 'app-actividades-list',
  imports: [CommonModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './actividades-list.component.html',
  styleUrl: './actividades-list.component.scss',
})
export class ActividadesListComponent {
  private readonly service = inject(ActividadesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  protected readonly auth = inject(AuthService);

  protected readonly club = toSignal(resolvedClub$(this.route, this.router), {
    initialValue: findResolvedClub(this.route),
  });

  protected readonly estado = signal<ActividadEstado | ''>('');
  protected readonly page = signal(1);

  protected readonly list = this.service.list;
  protected readonly loading = this.service.loading;
  protected readonly error = this.service.error;

  protected readonly actividades = computed<Actividad[]>(() => this.list()?.data ?? []);
  protected readonly totalPages = computed(() => this.list()?.last_page ?? 1);

  protected readonly canManage = computed(() => {
    const c = this.club();
    if (!c) return false;
    return this.auth.isSuperAdmin() || this.auth.isAdminOf(c.id) || this.auth.isGuideOf(c.id);
  });

  constructor() {
    effect(() => {
      const c = this.club();
      const e = this.estado();
      const p = this.page();
      if (!c) return;
      void this.service.load(c.id, {
        estado: (e || undefined) as ActividadEstado | undefined,
        page: p,
      });
    });
  }

  protected setEstado(e: ActividadEstado | ''): void {
    this.estado.set(e);
    this.page.set(1);
  }

  protected goToPage(p: number): void {
    if (p < 1 || p > this.totalPages()) return;
    this.page.set(p);
  }
}

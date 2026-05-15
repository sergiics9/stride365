import { CommonModule } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';

import Swal from 'sweetalert2';

import { AuthService } from '../../../core/auth/auth.service';
import { ToastService } from '../../../core/toast/toast.service';
import { Club, Comunicado } from '../../../shared/models';
import { findResolvedClub, resolvedClub$ } from '../../../shared/utils/resolved-club-from-route.util';
import { toApiError } from '../../../shared/utils/api-error.util';
import { ComunicadosService } from '../comunicados.service';

@Component({
  selector: 'app-comunicados-list',
  imports: [CommonModule, ReactiveFormsModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './comunicados-list.component.html',
  styleUrl: './comunicados-list.component.scss',
})
export class ComunicadosListComponent {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(ComunicadosService);
  private readonly toast = inject(ToastService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  protected readonly auth = inject(AuthService);

  protected readonly club = toSignal(resolvedClub$(this.route, this.router), {
    initialValue: findResolvedClub(this.route),
  });

  protected readonly form: FormGroup = this.fb.group({
    titulo: ['', [Validators.required, Validators.maxLength(255)]],
    contenido: ['', [Validators.required]],
  });

  protected readonly showCreate = signal(false);
  protected readonly page = signal(1);

  protected readonly list = this.service.list;
  protected readonly loading = this.service.loading;
  protected readonly error = this.service.error;
  protected readonly saving = this.service.saving;

  protected readonly comunicados = computed<Comunicado[]>(() => this.list()?.data ?? []);
  protected readonly totalPages = computed(() => this.list()?.last_page ?? 1);

  protected readonly canManage = computed(() => {
    const c = this.club();
    if (!c) return false;
    return this.auth.isSuperAdmin() || this.auth.isAdminOf(c.id) || this.auth.isGuideOf(c.id);
  });

  constructor() {
    effect(() => {
      const c = this.club();
      const p = this.page();
      if (!c) return;
      void this.service.load(c.id, p);
    });
  }

  protected toggleCreate(): void {
    this.showCreate.update((v) => !v);
  }

  protected async submit(): Promise<void> {
    const c = this.club();
    if (!c) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    try {
      await this.service.create(c.id, this.form.getRawValue());
      this.toast.success('Comunicado publicado.');
      this.form.reset();
      this.showCreate.set(false);
      await this.service.load(c.id);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected async remove(com: Comunicado): Promise<void> {
    const c = this.club();
    if (!c) return;
    const { isConfirmed } = await Swal.fire({
      title: '¿Eliminar comunicado?',
      text: `«${com.titulo}» será eliminado permanentemente.`,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
    });
    if (!isConfirmed) return;
    try {
      await this.service.destroy(c.id, com.id);
      this.toast.success('Comunicado eliminado.');
      await this.service.load(c.id);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected goToPage(p: number): void {
    if (p < 1 || p > this.totalPages()) return;
    this.page.set(p);
  }
}

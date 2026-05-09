import { CommonModule } from '@angular/common';
import {
  ChangeDetectionStrategy,
  Component,
  computed,
  effect,
  inject,
  signal,
} from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router } from '@angular/router';
import { debounceTime, map } from 'rxjs/operators';

import { ToastService } from '../../../core/toast/toast.service';
import { Club, MembershipStatus, Socio } from '../../../shared/models';
import {
  findResolvedClub,
  resolvedClub$,
} from '../../../shared/utils/resolved-club-from-route.util';
import { toApiError } from '../../../shared/utils/api-error.util';
import { SociosService } from '../socios.service';

@Component({
  selector: 'app-socios-list',
  imports: [CommonModule, ReactiveFormsModule],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './socios-list.component.html',
  styleUrl: './socios-list.component.scss',
})
export class SociosListComponent {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(SociosService);
  private readonly toast = inject(ToastService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);

  protected readonly club = toSignal(resolvedClub$(this.route, this.router), {
    initialValue: findResolvedClub(this.route),
  });

  protected readonly searchForm: FormGroup = this.fb.group({
    q: [''],
    estado: [''],
    guide: [''],
  });

  protected readonly createForm: FormGroup = this.fb.group({
    email: ['', [Validators.required, Validators.email]],
    nombre: [''],
    apellido: [''],
    telefono: [''],
  });

  protected readonly editingId = signal<number | null>(null);
  protected readonly editForm: FormGroup = this.fb.group({
    nombre: [''],
    apellido: [''],
    telefono: [''],
    is_guide: [false],
    status: [''],
  });

  protected readonly showCreate = signal(false);
  protected readonly page = signal(1);

  protected readonly list = this.service.list;
  protected readonly loading = this.service.loading;
  protected readonly error = this.service.error;
  protected readonly saving = this.service.saving;

  private readonly searchValues = toSignal(this.searchForm.valueChanges.pipe(debounceTime(300)), {
    initialValue: null,
  });

  protected readonly socios = computed(() => this.list()?.data ?? []);
  protected readonly totalPages = computed(() => this.list()?.last_page ?? 1);

  constructor() {
    effect(() => {
      const c = this.club();
      this.searchValues();
      const p = this.page();
      if (!c) return;
      const v = this.searchForm.getRawValue();
      void this.service.load(c.id, {
        q: v.q || undefined,
        estado: v.estado || undefined,
        guide: v.guide === '' ? undefined : v.guide === 'true',
        page: p,
      });
    });
  }

  protected goToPage(p: number): void {
    if (p < 1 || p > this.totalPages()) return;
    this.page.set(p);
  }

  protected toggleCreate(): void {
    this.showCreate.update((v) => !v);
  }

  protected async submitCreate(): Promise<void> {
    const c = this.club();
    if (!c) return;
    if (this.createForm.invalid) {
      this.createForm.markAllAsTouched();
      return;
    }
    try {
      await this.service.create(c.id, this.createForm.getRawValue());
      this.toast.success('Socio creado.');
      this.createForm.reset();
      this.showCreate.set(false);
      await this.service.load(c.id);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected startEdit(s: Socio): void {
    this.editingId.set(s.id);
    this.editForm.patchValue({
      nombre: s.user?.nombre ?? '',
      apellido: s.user?.apellido ?? '',
      telefono: s.user?.telefono ?? '',
      is_guide: s.is_guide,
      status: s.status,
    });
  }

  protected cancelEdit(): void {
    this.editingId.set(null);
  }

  protected async submitEdit(): Promise<void> {
    const c = this.club();
    const id = this.editingId();
    if (!c || !id) return;
    try {
      await this.service.update(c.id, id, this.editForm.getRawValue());
      this.toast.success('Socio actualizado.');
      this.editingId.set(null);
      await this.service.load(c.id);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected async giveLeave(s: Socio): Promise<void> {
    const c = this.club();
    if (!c) return;
    const motivo = prompt('Motivo de la baja (opcional):');
    if (motivo === null) return;
    if (!confirm(`¿Confirmar baja de ${s.user?.nombre ?? s.user?.email}?`)) return;
    try {
      await this.service.destroy(c.id, s.id, motivo || undefined);
      this.toast.success('Socio dado de baja.');
      await this.service.load(c.id);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected statusLabel(s: MembershipStatus): string {
    return s;
  }
}

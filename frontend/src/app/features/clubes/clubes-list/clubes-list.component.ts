import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { debounceTime } from 'rxjs/operators';

import { AuthService } from '../../../core/auth/auth.service';
import { ToastService } from '../../../core/toast/toast.service';
import { Club } from '../../../shared/models';
import { toApiError } from '../../../shared/utils/api-error.util';
import { ClubesService } from '../clubes.service';

@Component({
  selector: 'app-clubes-list',
  imports: [ReactiveFormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './clubes-list.component.html',
  styleUrl: './clubes-list.component.scss',
})
export class ClubesListComponent {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(ClubesService);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);

  protected readonly form: FormGroup = this.fb.group({
    q: [''],
  });

  protected readonly list = this.service.list;
  protected readonly loading = this.service.listLoading;
  protected readonly error = this.service.listError;

  protected readonly page = signal(1);

  private readonly searchSignal = toSignal(
    this.form.valueChanges.pipe(debounceTime(300)),
    { initialValue: null },
  );

  protected readonly clubes = computed(() => this.list()?.data ?? []);
  protected readonly totalPages = computed(() => this.list()?.last_page ?? 1);

  protected readonly myClubIds = computed(
    () => new Set(this.auth.activeMemberships().map((m) => m.club_id)),
  );

  protected readonly sortedClubes = computed(() => {
    const ids = this.myClubIds();
    return [...this.clubes()].sort((a, b) => {
      const aMine = ids.has(a.id) ? 0 : 1;
      const bMine = ids.has(b.id) ? 0 : 1;
      return aMine - bMine;
    });
  });

  protected isMyClub(c: Club): boolean {
    return this.myClubIds().has(c.id);
  }

  constructor() {
    effect(() => {
      this.searchSignal();
      const p = this.page();
      const q = this.form.getRawValue().q;
      void this.service.loadList({ q: q || undefined, page: p });
    });
  }

  protected goToPage(p: number): void {
    if (p < 1 || p > this.totalPages()) return;
    this.page.set(p);
  }

  protected canRequestClub(): boolean {
    return !this.auth.adminMembership();
  }

  protected async deleteClub(c: Club): Promise<void> {
    if (
      !confirm(
        `¿Eliminar el club «${c.nombre}»? Se borrarán socios, actividades y datos asociados. Esta acción no se puede deshacer.`,
      )
    ) {
      return;
    }
    try {
      await this.service.delete(c.id);
      this.toast.success('Club eliminado.');
      void this.service.loadList({
        q: this.form.getRawValue().q || undefined,
        page: this.page(),
      });
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }
}

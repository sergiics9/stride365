import { ChangeDetectionStrategy, Component, computed, effect, inject, signal } from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, FormGroup, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { debounceTime } from 'rxjs/operators';

import { AuthService } from '../../../core/auth/auth.service';
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
}

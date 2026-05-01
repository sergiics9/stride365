import { DatePipe } from '@angular/common';
import {
  ChangeDetectionStrategy,
  Component,
  computed,
  effect,
  inject,
  signal,
} from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { FormBuilder, ReactiveFormsModule } from '@angular/forms';
import { RouterLink } from '@angular/router';
import { debounceTime, startWith } from 'rxjs';

import { RelativeDatePipe } from '../../../shared/pipes/relative-date.pipe';
import { TruncatePipe } from '../../../shared/pipes/truncate.pipe';
import { EMPTY_FILTERS, FeedFilters, FeedService } from '../feed.service';

@Component({
  selector: 'app-feed-list',
  imports: [DatePipe, ReactiveFormsModule, RouterLink, RelativeDatePipe, TruncatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './feed-list.component.html',
  styleUrl: './feed-list.component.scss',
})
export class FeedListComponent {
  private readonly fb = inject(FormBuilder);
  protected readonly feedService = inject(FeedService);

  protected readonly form = this.fb.nonNullable.group({
    desde: [''],
    hasta: [''],
  });

  protected readonly page = signal(1);

  private readonly filters = toSignal(
    this.form.valueChanges.pipe(startWith(null), debounceTime(300)),
    { initialValue: null },
  );

  protected readonly hasResults = computed(() => this.feedService.publicaciones().length > 0);

  constructor() {
    effect(() => {
      this.filters();
      const p = this.page();
      void this.feedService.loadList(this.toFilters(), p);
    });
  }

  protected resetFilters(): void {
    this.form.reset({ desde: '', hasta: '' });
    this.page.set(1);
  }

  protected goToPage(target: number): void {
    const last = this.feedService.lastPage();
    const next = Math.min(Math.max(1, target), last);
    if (next !== this.page()) this.page.set(next);
  }

  private toFilters(): FeedFilters {
    const value = this.form.getRawValue();
    return {
      ...EMPTY_FILTERS,
      desde: value.desde || null,
      hasta: value.hasta || null,
    };
  }
}

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
import { Meta, Title } from '@angular/platform-browser';
import { RouterLink } from '@angular/router';
import { debounceTime, startWith } from 'rxjs';

import Swal from 'sweetalert2';

import { ToastService } from '../../../core/toast/toast.service';
import { toApiError } from '../../../shared/utils/api-error.util';
import { RelativeDatePipe } from '../../../shared/pipes/relative-date.pipe';
import { TruncatePipe } from '../../../shared/pipes/truncate.pipe';
import { MiniTrackThumbnailComponent } from '../../../shared/components/mini-track-thumbnail/mini-track-thumbnail.component';
import { EMPTY_FILTERS, FeedFilters, FeedService } from '../feed.service';

@Component({
  selector: 'app-feed-list',
  imports: [
    DatePipe,
    ReactiveFormsModule,
    RouterLink,
    RelativeDatePipe,
    TruncatePipe,
    MiniTrackThumbnailComponent,
  ],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './feed-list.component.html',
  styleUrl: './feed-list.component.scss',
})
export class FeedListComponent {
  private readonly fb = inject(FormBuilder);
  private readonly title = inject(Title);
  private readonly meta = inject(Meta);
  private readonly toast = inject(ToastService);
  protected readonly feedService = inject(FeedService);

  protected readonly importingGpx = signal(false);

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
    this.title.setTitle('Feed — actividades en directo');
    this.meta.updateTag({
      name: 'description',
      content:
        'Registra tus salidas con GPS o importa un GPX. Explora actividades tipo Strava publicadas por la comunidad.',
    });
    this.meta.updateTag({ property: 'og:title', content: 'Feed — actividades en directo' });
    this.meta.updateTag({
      property: 'og:description',
      content: 'Registro en vivo e importación GPX. Mapas y métricas de cada actividad.',
    });
    this.meta.updateTag({ property: 'og:type', content: 'website' });

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

  protected async onGpxSelected(ev: Event): Promise<void> {
    const input = ev.target as HTMLInputElement;
    const file = input.files?.[0];
    input.value = '';
    if (!file) return;

    const { value: titulo, isConfirmed } = await Swal.fire({
      title: 'Importar GPX',
      input: 'text',
      inputLabel: 'Título de la actividad (opcional)',
      inputPlaceholder: 'Mi salida del domingo…',
      showCancelButton: true,
      confirmButtonText: 'Importar',
      cancelButtonText: 'Cancelar',
    });
    if (!isConfirmed) return;

    this.importingGpx.set(true);
    try {
      await this.feedService.importGpx(file, titulo?.trim() || undefined);
      this.toast.success('GPX importado y publicado en el feed.');
      void this.feedService.loadList(this.toFilters(), this.page());
    } catch (err) {
      this.toast.error(toApiError(err).message);
    } finally {
      this.importingGpx.set(false);
    }
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

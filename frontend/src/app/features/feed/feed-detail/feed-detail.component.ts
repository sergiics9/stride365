import { DatePipe } from '@angular/common';
import {
  AfterViewInit,
  ChangeDetectionStrategy,
  Component,
  DestroyRef,
  ElementRef,
  computed,
  effect,
  inject,
  input,
  signal,
  viewChild,
} from '@angular/core';
import { RouterLink } from '@angular/router';

import { MediaItem, PublicacionFeed } from '../../../shared/models';

interface MapBundle {
  L: typeof import('leaflet');
}

@Component({
  selector: 'app-feed-detail',
  imports: [DatePipe, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './feed-detail.component.html',
  styleUrl: './feed-detail.component.scss',
})
export class FeedDetailComponent implements AfterViewInit {
  private readonly destroyRef = inject(DestroyRef);

  readonly publicacion = input<PublicacionFeed | null>(null);

  protected readonly mapContainer = viewChild<ElementRef<HTMLDivElement>>('mapContainer');
  protected readonly mapStatus = signal<'idle' | 'loading' | 'ready' | 'error'>('idle');

  protected readonly imagenes = computed<MediaItem[]>(() =>
    (this.publicacion()?.media ?? []).filter((m) => m.mime_type?.startsWith('image/')),
  );

  protected readonly gpx = computed<MediaItem | null>(() => {
    const media = this.publicacion()?.media ?? [];
    return (
      media.find(
        (m) =>
          m.mime_type === 'application/gpx+xml' || m.file_name?.toLowerCase().endsWith('.gpx'),
      ) ?? null
    );
  });

  private mapInstance: import('leaflet').Map | null = null;
  private mapBundle: MapBundle | null = null;

  constructor() {
    effect(() => {
      const gpxItem = this.gpx();
      const container = this.mapContainer()?.nativeElement;
      if (!container || !gpxItem?.original_url) return;
      void this.renderTrack(container, gpxItem.original_url);
    });

    this.destroyRef.onDestroy(() => {
      this.mapInstance?.remove();
      this.mapInstance = null;
    });
  }

  ngAfterViewInit(): void {
    /* effect handles initialization; this hook ensures viewChild is settled */
  }

  private async renderTrack(container: HTMLDivElement, gpxUrl: string): Promise<void> {
    this.mapStatus.set('loading');

    try {
      if (!this.mapBundle) {
        const L = await import('leaflet');
        await import('leaflet-gpx');
        this.mapBundle = { L };
      }
      const { L } = this.mapBundle;

      this.mapInstance?.remove();

      const map = L.map(container, { scrollWheelZoom: false }).setView([40.4168, -3.7038], 6);
      L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap',
        maxZoom: 18,
      }).addTo(map);

      const gpxConstructor = (L as unknown as {
        GPX: new (
          url: string,
          opts: Record<string, unknown>,
        ) => import('leaflet').FeatureGroup & {
          on(event: 'loaded', cb: (e: { target: import('leaflet').FeatureGroup }) => void): void;
          on(event: 'error', cb: (e: unknown) => void): void;
        };
      }).GPX;

      const gpxLayer = new gpxConstructor(gpxUrl, {
        async: true,
        marker_options: {
          startIconUrl: '',
          endIconUrl: '',
          shadowUrl: '',
        },
      });

      gpxLayer.on('loaded', (e) => {
        map.fitBounds(e.target.getBounds(), { padding: [16, 16] });
        this.mapStatus.set('ready');
      });
      gpxLayer.on('error', () => this.mapStatus.set('error'));
      gpxLayer.addTo(map);

      this.mapInstance = map;
    } catch {
      this.mapStatus.set('error');
    }
  }
}

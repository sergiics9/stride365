import { DatePipe, TitleCasePipe } from '@angular/common';
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
import { Meta, Title } from '@angular/platform-browser';
import { RouterLink } from '@angular/router';

import { MediaItem, PublicacionFeed } from '../../../shared/models';
import { OSM_ATTRIBUTION, OSM_TILE_LAYER_URL } from '../../../shared/map/osm-tiles';
import {
  formatDurationHms,
  formatPaceMinPerKm,
} from '../../../shared/utils/activity-metrics-display.util';
import { stripGeoJsonCoordinatesTo2D } from '../../../shared/utils/geojson-2d.util';
import { loadLeaflet } from '../../../shared/utils/leaflet-loader.util';

interface MapBundle {
  L: typeof import('leaflet');
}

@Component({
  selector: 'app-feed-detail',
  imports: [DatePipe, RouterLink, TitleCasePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './feed-detail.component.html',
  styleUrl: './feed-detail.component.scss',
})
export class FeedDetailComponent implements AfterViewInit {
  private readonly destroyRef = inject(DestroyRef);
  private readonly title = inject(Title);
  private readonly meta = inject(Meta);

  readonly publicacion = input<PublicacionFeed | null>(null);

  private readonly seoEffect = effect(() => {
    const p = this.publicacion();
    if (!p) return;
    const titulo = p.titulo ?? 'Actividad';
    this.title.setTitle(`${titulo} — Feed`);
    const desc = (p.resumen ?? p.contenido ?? '').toString().slice(0, 200);
    if (desc) {
      this.meta.updateTag({ name: 'description', content: desc });
      this.meta.updateTag({ property: 'og:description', content: desc });
    }
    this.meta.updateTag({ property: 'og:title', content: titulo });
    this.meta.updateTag({ property: 'og:type', content: 'article' });
  });

  protected readonly mapContainer = viewChild<ElementRef<HTMLDivElement>>('mapContainer');
  protected readonly mapStatus = signal<'idle' | 'loading' | 'ready' | 'error'>('idle');

  protected formatActividadDuracion = formatDurationHms;
  protected formatActividadPace = formatPaceMinPerKm;

  protected readonly imagenes = computed<MediaItem[]>(() =>
    (this.publicacion()?.media ?? []).filter((m) => m.mime_type?.startsWith('image/')),
  );

  protected readonly trackGeoJson = computed(() => {
    const g = this.publicacion()?.actividad?.track_geojson;
    if (!g || typeof g !== 'object') return null;
    const coords = (g as { coordinates?: unknown }).coordinates;
    return Array.isArray(coords) && coords.length >= 2 ? g : null;
  });

  protected readonly gpx = computed<MediaItem | null>(() => {
    const media = this.publicacion()?.media ?? [];
    return (
      media.find(
        (m) => m.mime_type === 'application/gpx+xml' || m.file_name?.toLowerCase().endsWith('.gpx'),
      ) ?? null
    );
  });

  protected readonly hasMap = computed(
    () => this.trackGeoJson() !== null || this.gpx()?.original_url,
  );

  private mapInstance: import('leaflet').Map | null = null;
  private mapBundle: MapBundle | null = null;

  constructor() {
    effect(() => {
      const container = this.mapContainer()?.nativeElement;
      if (!container || !this.hasMap()) return;

      const geo = this.trackGeoJson();
      if (geo) {
        void this.renderGeoJson(container, geo);
        return;
      }

      const gpxItem = this.gpx();
      if (gpxItem?.original_url) {
        void this.renderTrack(container, gpxItem.original_url);
      }
    });

    this.destroyRef.onDestroy(() => {
      this.mapInstance?.remove();
      this.mapInstance = null;
    });
  }

  ngAfterViewInit(): void {
    /* effect handles initialization */
  }

  private async renderGeoJson(container: HTMLDivElement, geojson: object): Promise<void> {
    this.mapStatus.set('loading');
    try {
      if (!this.mapBundle) {
        const L = await loadLeaflet();
        this.mapBundle = { L };
      }
      const { L } = this.mapBundle;
      this.mapInstance?.remove();

      const map = L.map(container, { scrollWheelZoom: false }).setView([40.4168, -3.7038], 6);
      L.tileLayer(OSM_TILE_LAYER_URL, {
        attribution: OSM_ATTRIBUTION,
        maxZoom: 19,
      }).addTo(map);

      const layer = L.geoJSON(stripGeoJsonCoordinatesTo2D(geojson) as never, {
        style: { color: '#fd7e14', weight: 4 },
      }).addTo(map);
      const bounds = layer.getBounds?.();
      if (bounds?.isValid?.()) {
        map.fitBounds(bounds, { padding: [16, 16] });
      }
      this.mapInstance = map;
      this.mapStatus.set('ready');
    } catch {
      this.mapStatus.set('error');
    }
  }

  private async renderTrack(container: HTMLDivElement, gpxUrl: string): Promise<void> {
    this.mapStatus.set('loading');

    try {
      if (!this.mapBundle) {
        const L = await loadLeaflet();
        await import('leaflet-gpx');
        this.mapBundle = { L };
      }
      const { L } = this.mapBundle;

      this.mapInstance?.remove();

      const map = L.map(container, { scrollWheelZoom: false }).setView([40.4168, -3.7038], 6);
      L.tileLayer(OSM_TILE_LAYER_URL, {
        attribution: OSM_ATTRIBUTION,
        maxZoom: 19,
      }).addTo(map);

      const gpxConstructor = (
        L as unknown as {
          GPX: new (
            url: string,
            opts: Record<string, unknown>,
          ) => import('leaflet').FeatureGroup & {
            on(event: 'loaded', cb: (e: { target: import('leaflet').FeatureGroup }) => void): void;
            on(event: 'error', cb: (e: unknown) => void): void;
          };
        }
      ).GPX;

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

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
import { FormsModule } from '@angular/forms';
import { Meta, Title } from '@angular/platform-browser';
import { Router, RouterLink } from '@angular/router';
import Swal from 'sweetalert2';

import { AuthService } from '../../../core/auth/auth.service';
import { MediaItem, PublicacionFeed } from '../../../shared/models';
import { OSM_ATTRIBUTION, OSM_TILE_LAYER_URL } from '../../../shared/map/osm-tiles';
import {
  formatDurationHms,
  formatPaceMinPerKm,
} from '../../../shared/utils/activity-metrics-display.util';
import { stripGeoJsonCoordinatesTo2D } from '../../../shared/utils/geojson-2d.util';
import { loadLeaflet } from '../../../shared/utils/leaflet-loader.util';
import { FeedService } from '../feed.service';

interface MapBundle {
  L: typeof import('leaflet');
}

@Component({
  selector: 'app-feed-detail',
  imports: [DatePipe, FormsModule, RouterLink, TitleCasePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './feed-detail.component.html',
  styleUrl: './feed-detail.component.scss',
})
export class FeedDetailComponent implements AfterViewInit {
  private readonly destroyRef = inject(DestroyRef);
  private readonly title = inject(Title);
  private readonly meta = inject(Meta);
  private readonly auth = inject(AuthService);
  private readonly feedService = inject(FeedService);
  private readonly router = inject(Router);

  
  readonly publicacion = input<PublicacionFeed | null>(null);

  private readonly _pub = signal<PublicacionFeed | null>(null);

  
  protected readonly pub = this._pub.asReadonly();

  private readonly seoEffect = effect(() => {
    const p = this._pub();
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
    (this._pub()?.media ?? []).filter((m) => m.mime_type?.startsWith('image/')),
  );

  
  protected readonly isOwner = computed(() => {
    const p = this._pub();
    const user = this.auth.user();
    if (!p || !user) return false;
    return p.user_id === user.id || this.auth.isSuperAdmin();
  });

  protected readonly trackGeoJson = computed(() => {
    const g = this._pub()?.actividad?.track_geojson;
    if (!g || typeof g !== 'object') return null;
    const coords = (g as { coordinates?: unknown }).coordinates;
    return Array.isArray(coords) && coords.length >= 2 ? g : null;
  });

  protected readonly gpx = computed<MediaItem | null>(() => {
    const media = this._pub()?.media ?? [];
    return (
      media.find(
        (m) => m.mime_type === 'application/gpx+xml' || m.file_name?.toLowerCase().endsWith('.gpx'),
      ) ?? null
    );
  });

  protected readonly hasMap = computed(
    () => this.trackGeoJson() !== null || this.gpx()?.original_url,
  );

  
  protected readonly showEditModal = signal(false);
  protected editTitulo = '';
  protected editDescripcion = '';
  protected readonly editLoading = signal(false);

  private mapInstance: import('leaflet').Map | null = null;
  private mapBundle: MapBundle | null = null;

  constructor() {
    effect(() => {
      const incoming = this.publicacion();
      if (incoming !== null) {
        this._pub.set(incoming);
      }
    });

    // El mapa puede venir como GeoJSON guardado en la actividad o como archivo GPX adjunto.
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
    
  }

  
  protected openEditModal(): void {
    const p = this._pub();
    this.editTitulo = p?.titulo ?? '';
    this.editDescripcion = p?.actividad?.descripcion ?? '';
    this.showEditModal.set(true);
  }

  protected closeEditModal(): void {
    this.showEditModal.set(false);
  }

  protected async submitEdit(): Promise<void> {
    const p = this._pub();
    if (!p || !this.editTitulo.trim()) return;

    this.editLoading.set(true);
    try {
      const updated = await this.feedService.updatePublicacion(p.id, {
        titulo: this.editTitulo.trim(),
        descripcion: this.editDescripcion.trim() || null,
      });
      this._pub.set(updated);
      this.showEditModal.set(false);
      void Swal.fire({
        toast: true,
        position: 'top-end',
        icon: 'success',
        title: 'Actividad actualizada.',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
      });
    } catch {
      void Swal.fire({
        icon: 'error',
        title: 'Error al guardar',
        text: 'No se pudo guardar los cambios. Inténtalo de nuevo.',
      });
    } finally {
      this.editLoading.set(false);
    }
  }

  protected async confirmDelete(): Promise<void> {
    const p = this._pub();
    if (!p) return;

    const { isConfirmed } = await Swal.fire({
      title: '¿Eliminar publicación?',
      text: 'Esta acción no se puede deshacer.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonColor: '#dc3545',
      confirmButtonText: 'Sí, eliminar',
      cancelButtonText: 'Cancelar',
    });
    if (!isConfirmed) return;

    try {
      await this.feedService.deletePublicacion(p.id);
      void this.router.navigate(['/feed']);
    } catch {
      void Swal.fire({
        icon: 'error',
        title: 'Error al eliminar',
        text: 'No se pudo eliminar la publicación. Inténtalo de nuevo.',
      });
    }
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

  // Cuando solo tenemos un GPX externo, leaflet-gpx lo descarga y dibuja la ruta.
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

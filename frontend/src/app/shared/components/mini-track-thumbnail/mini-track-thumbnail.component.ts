import {
  ChangeDetectionStrategy,
  ChangeDetectorRef,
  Component,
  computed,
  DestroyRef,
  effect,
  ElementRef,
  inject,
  input,
  viewChild,
} from '@angular/core';

import { OSM_ATTRIBUTION, OSM_TILE_LAYER_URL } from '../../map/osm-tiles';
import { stripGeoJsonCoordinatesTo2D } from '../../utils/geojson-2d.util';
import { loadLeaflet } from '../../utils/leaflet-loader.util';
import { extractLngLatPairsFromTrackGeoJson } from '../../utils/track-geojson-coords.util';

@Component({
  selector: 'app-mini-track-thumbnail',
  standalone: true,
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    <div
      class="mini-track"
      [class.mini-track--compact]="compact()"
      [attr.aria-label]="ariaLabel()"
      role="img"
    >
      @if (hasTrack()) {
        <div #mapEl class="mini-track__map leaflet-mini-host" role="presentation"></div>
      } @else {
        <div class="mini-track__empty" aria-hidden="true"></div>
      }
    </div>
  `,
  styles: `
    :host {
      display: block;
      width: 100%;
      min-width: 0;
    }
    .mini-track {
      width: 100%;
      background: #e9ecef;
    }
    .mini-track:not(.mini-track--compact) {
      height: 200px;
      min-height: 200px;
    }
    .mini-track--compact {
      height: 100px;
      min-height: 100px;
    }
    .mini-track__map {
      width: 100%;
      height: 100%;
      min-height: inherit;
      position: relative;
      z-index: 0;
    }
    .mini-track__empty {
      height: 200px;
      background: #e9ecef;
    }
    .mini-track--compact .mini-track__empty {
      height: 100px;
    }
  `,
})
export class MiniTrackThumbnailComponent {
  private readonly destroyRef = inject(DestroyRef);
  private readonly cdr = inject(ChangeDetectorRef);

  
  readonly track = input<unknown | null | undefined>(null);
  
  readonly compact = input(false);
  readonly ariaLabel = input('Mapa del recorrido');

  private readonly mapEl = viewChild<ElementRef<HTMLDivElement>>('mapEl');

  private map: import('leaflet').Map | null = null;
  // Contador para ignorar sincronizaciones antiguas si el track cambia muy rápido.
  private syncGeneration = 0;
  private resizeObserver: ResizeObserver | null = null;

  protected readonly hasTrack = computed(() => {
    const pairs = extractLngLatPairsFromTrackGeoJson(this.parseTrack(this.track()));
    return pairs.length >= 2;
  });

  constructor() {
    const eff = effect(() => {
      this.track();
      this.compact();
      this.hasTrack();
      this.mapEl();
      void this.syncMap();
    });

    this.destroyRef.onDestroy(() => {
      eff.destroy();
      this.teardownMap();
    });
  }

  private parseTrack(raw: unknown): unknown {
    if (typeof raw === 'string') {
      try {
        return JSON.parse(raw) as unknown;
      } catch {
        return null;
      }
    }
    return raw;
  }

  private disconnectResizeObserver(): void {
    this.resizeObserver?.disconnect();
    this.resizeObserver = null;
  }

  private async syncMap(): Promise<void> {
    const gen = ++this.syncGeneration;
    const raw = this.parseTrack(this.track());
    const el = this.mapEl()?.nativeElement;

    if (!raw || !el || !this.hasTrack()) {
      this.teardownMap();
      return;
    }

    this.teardownMap();

    // Leaflet necesita que el contenedor ya tenga tamaño real en pantalla.
    // Esperamos un par de frames y, si hace falta, un pequeño retardo.
    await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()));
    await new Promise<void>((resolve) => requestAnimationFrame(() => resolve()));

    if (gen !== this.syncGeneration || !el.isConnected) {
      return;
    }

    if (el.offsetWidth < 4 || el.offsetHeight < 4) {
      await new Promise<void>((r) => setTimeout(r, 120));
    }
    if (gen !== this.syncGeneration || !el.isConnected) {
      return;
    }

    const L = await loadLeaflet();
    if (gen !== this.syncGeneration || !el.isConnected) {
      return;
    }

    const geo = stripGeoJsonCoordinatesTo2D(raw) as object;

    // Miniatura de solo lectura: sin zoom ni arrastre, solo vista previa del track.
    this.map = L.map(el, {
      zoomControl: false,
      dragging: false,
      scrollWheelZoom: false,
      doubleClickZoom: false,
      boxZoom: false,
      keyboard: false,
      touchZoom: false,
      attributionControl: true,
    });
    this.map.invalidateSize();

    const tiles = L.tileLayer(OSM_TILE_LAYER_URL, {
      attribution: OSM_ATTRIBUTION,
      maxZoom: 19,
      maxNativeZoom: 19,
    }).addTo(this.map);

    tiles.on('load', () => {
      if (gen === this.syncGeneration) {
        this.map?.invalidateSize();
        this.cdr.markForCheck();
      }
    });

    const layer = L.geoJSON(geo as never, {
      style: { color: '#fd7e14', weight: 4 },
    }).addTo(this.map);

    const bounds = layer.getBounds?.();
    const mapInstance = this.map;

    this.resizeObserver = new ResizeObserver(() => {
      queueMicrotask(() => {
        if (gen === this.syncGeneration) {
          mapInstance.invalidateSize();
        }
      });
    });
    this.resizeObserver.observe(el);

    // Centramos el mapa en la ruta. No usamos whenReady aquí porque encadenarlo
    // con fitBounds/setView puede dejar el mapa sin cargar teselas.
    if (bounds?.isValid?.()) {
      mapInstance.fitBounds(bounds, { padding: [10, 10], maxZoom: 16 });
    } else {
      const pairs = extractLngLatPairsFromTrackGeoJson(raw);
      const [lng, lat] = pairs[0] ?? [0, 0];
      mapInstance.setView([lat, lng], 14);
    }

    requestAnimationFrame(() => {
      if (gen === this.syncGeneration) {
        mapInstance.invalidateSize();
        this.cdr.markForCheck();
      }
    });

    setTimeout(() => {
      if (gen === this.syncGeneration) {
        this.map?.invalidateSize();
        this.cdr.markForCheck();
      }
    }, 400);
  }

  private teardownMap(): void {
    this.disconnectResizeObserver();
    if (this.map) {
      this.map.remove();
      this.map = null;
    }
  }
}

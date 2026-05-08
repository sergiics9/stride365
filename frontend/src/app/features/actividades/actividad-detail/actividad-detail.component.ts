import { CommonModule } from '@angular/common';
import {
  AfterViewInit,
  ChangeDetectionStrategy,
  Component,
  ElementRef,
  OnDestroy,
  computed,
  effect,
  inject,
  signal,
  viewChild,
} from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { map } from 'rxjs/operators';

import { stripGeoJsonCoordinatesTo2D } from '../../../shared/utils/geojson-2d.util';
import { AuthService } from '../../../core/auth/auth.service';
import { OSM_ATTRIBUTION, OSM_TILE_LAYER_URL } from '../../../shared/map/osm-tiles';
import { ToastService } from '../../../core/toast/toast.service';
import { Actividad, Club, Inscripcion } from '../../../shared/models';
import { findResolvedClub, resolvedClub$ } from '../../../shared/utils/resolved-club-from-route.util';
import { toApiError } from '../../../shared/utils/api-error.util';
import { ActividadesService } from '../actividades.service';

@Component({
  selector: 'app-actividad-detail',
  imports: [CommonModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './actividad-detail.component.html',
  styleUrl: './actividad-detail.component.scss',
})
export class ActividadDetailComponent implements AfterViewInit, OnDestroy {
  private readonly service = inject(ActividadesService);
  private readonly route = inject(ActivatedRoute);
  private readonly router = inject(Router);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);

  protected readonly club = toSignal(resolvedClub$(this.route, this.router), {
    initialValue: findResolvedClub(this.route),
  });

  protected readonly id = toSignal(
    this.route.paramMap.pipe(map((p) => Number(p.get('id') ?? 0))),
    { initialValue: 0 },
  );

  protected readonly actividad = signal<Actividad | null>(null);
  protected readonly inscripciones = signal<Inscripcion[]>([]);
  protected readonly loading = signal(true);
  protected readonly inscribiendo = signal(false);

  protected readonly canManage = computed(() => {
    const c = this.club();
    if (!c) return false;
    return this.auth.isSuperAdmin() || this.auth.isAdminOf(c.id) || this.auth.isGuideOf(c.id);
  });

  protected readonly canSubscribe = computed(() => {
    const a = this.actividad();
    const c = this.club();
    if (!a || !c) return false;
    if (a.estado === 'cancelada' || a.estado === 'finalizada') return false;
    return this.auth.isSocioOf(c.id);
  });

  protected readonly miInscripcion = computed<Inscripcion | null>(() => {
    const me = this.auth.user()?.id;
    if (!me) return null;
    return this.inscripciones().find((i) => i.user_id === me) ?? null;
  });

  protected readonly plazasInfo = computed(() => {
    const a = this.actividad();
    const total = this.inscripciones().length;
    if (!a) return null;
    if (a.cupo_maximo) return `${total} / ${a.cupo_maximo} plazas`;
    return `${total} inscritos`;
  });

  private mapEl = viewChild<ElementRef<HTMLDivElement>>('mapEl');
  private map: any = null;

  constructor() {
    effect(() => {
      const c = this.club();
      const id = this.id();
      if (c && id) {
        void this.load(c.id, id);
      }
    });
  }

  ngAfterViewInit(): void {
    setTimeout(() => this.refreshMap(), 0);
  }

  ngOnDestroy(): void {
    this.map?.remove();
    this.map = null;
  }

  private async load(clubId: number, id: number): Promise<void> {
    this.loading.set(true);
    try {
      const a = await this.service.getById(clubId, id);
      this.actividad.set(a);
      if (a) {
        const ins = await this.service.listInscripciones(a.id);
        this.inscripciones.set(ins.data);
      }
    } finally {
      this.loading.set(false);
      this.refreshMap();
    }
  }

  private async refreshMap(): Promise<void> {
    const a = this.actividad();
    const el = this.mapEl()?.nativeElement;
    if (!a?.track_geojson || !el) return;
    const L = await import('leaflet');
    if (!this.map) {
      this.map = L.map(el).setView([41.39, 2.16], 11);
      L.tileLayer(OSM_TILE_LAYER_URL, {
        attribution: OSM_ATTRIBUTION,
        maxZoom: 19,
      }).addTo(this.map);
    } else {
      this.map.eachLayer((layer: any) => {
        if (layer instanceof L.Polyline) this.map.removeLayer(layer);
      });
    }
    const gj = stripGeoJsonCoordinatesTo2D(a.track_geojson);
    const layer = L.geoJSON(gj as any, {
      style: { color: '#0d6efd', weight: 4 },
    }).addTo(this.map);
    const bounds = layer.getBounds?.();
    if (bounds && bounds.isValid?.()) this.map.fitBounds(bounds);
  }

  protected async inscribirme(): Promise<void> {
    const a = this.actividad();
    if (!a) return;
    this.inscribiendo.set(true);
    try {
      const inscripcion = await this.service.inscribir(a.id);
      this.inscripciones.update((list) => [inscripcion, ...list]);
      this.toast.success('Te has inscrito. Recibirás un email de confirmación.');
    } catch (err) {
      this.toast.error(toApiError(err).message);
    } finally {
      this.inscribiendo.set(false);
    }
  }

  protected async cancelarInscripcion(ins: Inscripcion): Promise<void> {
    const a = this.actividad();
    if (!a) return;
    const motivo = prompt('Motivo de la cancelación (opcional):');
    if (motivo === null) return;
    try {
      await this.service.cancelarInscripcion(a.id, ins.id, motivo || undefined);
      this.inscripciones.update((list) => list.filter((i) => i.id !== ins.id));
      this.toast.success('Inscripción cancelada.');
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected async cancelarActividad(): Promise<void> {
    const c = this.club();
    const a = this.actividad();
    if (!c || !a) return;
    const motivo = prompt('Motivo de la cancelación:');
    if (!motivo) return;
    try {
      await this.service.cancel(c.id, a.id, motivo);
      this.toast.success('Actividad cancelada.');
      await this.load(c.id, a.id);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected async finalizar(): Promise<void> {
    const c = this.club();
    const a = this.actividad();
    if (!c || !a) return;
    try {
      await this.service.finish(c.id, a.id, {});
      this.toast.success('Actividad finalizada.');
      await this.load(c.id, a.id);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }
}

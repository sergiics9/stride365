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
import { FormBuilder, FormGroup, ReactiveFormsModule, Validators } from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';
import { map } from 'rxjs/operators';

import { ToastService } from '../../../core/toast/toast.service';
import { OSM_ATTRIBUTION, OSM_TILE_LAYER_URL } from '../../../shared/map/osm-tiles';
import {
  Actividad,
  CreateActividadRequest,
  ModoCreacion,
  Socio,
  UpdateActividadRequest,
} from '../../../shared/models';
import { findResolvedClub, resolvedClub$ } from '../../../shared/utils/resolved-club-from-route.util';
import { toApiError } from '../../../shared/utils/api-error.util';
import { SociosService } from '../../socios/socios.service';
import { ActividadesService } from '../actividades.service';

type Coord = [number, number] | [number, number, number];

const ELEV_NOISE_M = 3;

function positiveElevationGainM(elevations: number[]): number | null {
  if (elevations.length < 2) {
    return null;
  }
  let gain = 0;
  for (let i = 1; i < elevations.length; i++) {
    const d = elevations[i] - elevations[i - 1];
    if (d > ELEV_NOISE_M) {
      gain += d;
    }
  }
  return Math.round(gain);
}

const RADIO_KM = 6371;

function haversineKm(a: Coord, b: Coord): number {
  const lat1 = (a[0] * Math.PI) / 180;
  const lat2 = (b[0] * Math.PI) / 180;
  const dLat = ((b[0] - a[0]) * Math.PI) / 180;
  const dLng = ((b[1] - a[1]) * Math.PI) / 180;
  const x =
    Math.sin(dLat / 2) * Math.sin(dLat / 2) +
    Math.cos(lat1) * Math.cos(lat2) * Math.sin(dLng / 2) * Math.sin(dLng / 2);
  return 2 * RADIO_KM * Math.asin(Math.min(1, Math.sqrt(x)));
}

function polylineLengthKm(points: Coord[]): number {
  let sum = 0;
  for (let i = 1; i < points.length; i++) {
    sum += haversineKm(points[i - 1], points[i]);
  }
  return sum;
}

@Component({
  selector: 'app-actividad-form',
  imports: [CommonModule, ReactiveFormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './actividad-form.component.html',
  styleUrl: './actividad-form.component.scss',
})
export class ActividadFormComponent implements AfterViewInit, OnDestroy {
  private readonly fb = inject(FormBuilder);
  private readonly service = inject(ActividadesService);
  private readonly socios = inject(SociosService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  protected readonly club = toSignal(resolvedClub$(this.route, this.router), {
    initialValue: findResolvedClub(this.route),
  });

  protected readonly editId = toSignal(
    this.route.paramMap.pipe(map((p) => (p.get('id') ? Number(p.get('id')) : null))),
    { initialValue: null as number | null },
  );

  protected readonly form: FormGroup = this.fb.group({
    titulo: ['', [Validators.required, Validators.maxLength(255)]],
    descripcion: [''],
    fecha_inicio: ['', [Validators.required]],
    fecha_fin: [''],
    lugar: [''],
    punto_encuentro: [''],
    material_necesario: [''],
    modalidad: [''],
    dificultad: [''],
    cupo_maximo: [null],
    costo: [null],
    modo_creacion: ['dibujada' as ModoCreacion, [Validators.required]],
  });

  protected readonly trackPoints = signal<Coord[]>([]);
  protected readonly modoActual = signal<ModoCreacion>('dibujada');
  protected readonly submitting = signal(false);
  protected readonly serverError = signal<string | null>(null);

  /** Socios del club con rol guía (`is_guide`), para asignar a la actividad. */
  protected readonly guiasDisponibles = signal<Socio[]>([]);
  /** IDs de usuario (`users.id`) enlazados en `actividad_guia`. */
  protected readonly guiaUserIdsSeleccionados = signal<number[]>([]);

  protected readonly hasTrack = computed(() => this.trackPoints().length >= 2);

  protected readonly trackDistanceKm = computed<number | null>(() => {
    const pts = this.trackPoints();
    if (pts.length < 2) {
      return null;
    }
    return Math.round(polylineLengthKm(pts) * 100) / 100;
  });

  protected readonly desnivelPreviewM = computed<number | null>(() => {
    const pts = this.trackPoints();
    const eles = pts
      .map((p) => (p.length > 2 ? p[2] : null))
      .filter((x): x is number => x != null && Number.isFinite(x));
    if (eles.length < 2) {
      return null;
    }
    return positiveElevationGainM(eles);
  });

  private mapEl = viewChild<ElementRef<HTMLDivElement>>('mapEl');
  private gpxInput = viewChild<ElementRef<HTMLInputElement>>('gpxInput');
  private map: any = null;
  private polyline: any = null;
  private clickHandler: ((e: any) => void) | null = null;

  constructor() {
    effect(() => {
      const id = this.editId();
      const c = this.club();
      if (id && c) {
        void this.loadEditData(c.id, id);
      }
    });

    effect(() => {
      const c = this.club();
      const editing = this.editId();
      if (c) {
        void this.loadGuiasDelClub(c.id);
        if (!editing) {
          this.guiaUserIdsSeleccionados.set([]);
        }
      } else {
        this.guiasDisponibles.set([]);
        this.guiaUserIdsSeleccionados.set([]);
      }
    });

    effect(() => {
      const m = this.modoActual();
      void this.handleModoChange(m);
    });
  }

  ngAfterViewInit(): void {
    setTimeout(() => this.initMap(), 0);
  }

  ngOnDestroy(): void {
    this.detachClickHandler();
    this.map?.remove();
    this.map = null;
  }

  protected onModoCreacionChange(): void {
    const v = this.form.get('modo_creacion')?.value as ModoCreacion;
    if (v !== 'dibujada' && v !== 'importada') {
      return;
    }
    if (this.modoActual() !== v) {
      this.resetTrack();
    }
    this.modoActual.set(v);
  }

  private async initMap(): Promise<void> {
    const el = this.mapEl()?.nativeElement;
    if (!el || this.map) return;
    const L = await import('leaflet');
    this.map = L.map(el).setView([41.39, 2.16], 11);
    L.tileLayer(OSM_TILE_LAYER_URL, {
      attribution: OSM_ATTRIBUTION,
      maxZoom: 19,
    }).addTo(this.map);
    this.polyline = L.polyline([], { color: '#0d6efd', weight: 4 }).addTo(this.map);
    await this.handleModoChange(this.modoActual());
  }

  private async handleModoChange(modo: ModoCreacion): Promise<void> {
    if (!this.map) return;
    this.detachClickHandler();
    if (modo === 'dibujada') {
      const L = await import('leaflet');
      this.clickHandler = (e: any) => {
        const next = [...this.trackPoints(), [e.latlng.lat, e.latlng.lng] as Coord];
        this.trackPoints.set(next);
        this.polyline.setLatLngs(next.map((p) => L.latLng(p[0], p[1])));
      };
      this.map.on('click', this.clickHandler);
    }
  }

  private detachClickHandler(): void {
    if (this.map && this.clickHandler) {
      this.map.off('click', this.clickHandler);
      this.clickHandler = null;
    }
  }

  protected resetTrack(): void {
    this.trackPoints.set([]);
    this.polyline?.setLatLngs([]);
    const inp = this.gpxInput()?.nativeElement;
    if (inp) {
      inp.value = '';
    }
  }

  protected async onGpxFile(event: Event): Promise<void> {
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0];
    if (!file) return;
    const text = await file.text();
    const points = this.parseGpx(text);
    if (points.length < 2) {
      this.toast.warning('No se encontraron al menos dos puntos válidos en el GPX (trkpt o rtept).');
      input.value = '';
      return;
    }
    this.trackPoints.set(points);
    const L = await import('leaflet');
    if (this.polyline) {
      this.polyline.setLatLngs(points.map((p) => L.latLng(p[0], p[1])));
      const bounds = this.polyline.getBounds?.();
      if (bounds && bounds.isValid?.()) {
        this.map.fitBounds(bounds);
      }
    }
    this.toast.success(`GPX importado: ${points.length} puntos.`);
  }

  private parseGpx(xml: string): Coord[] {
    try {
      const doc = new DOMParser().parseFromString(xml, 'application/xml');
      const fromPts = (tag: string): Coord[] => {
        const nodes = doc.getElementsByTagName(tag);
        const out: Coord[] = [];
        for (let i = 0; i < nodes.length; i++) {
          const lat = parseFloat(nodes[i].getAttribute('lat') ?? '');
          const lng = parseFloat(nodes[i].getAttribute('lon') ?? '');
          if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
            continue;
          }
          const el = nodes[i].getElementsByTagName('ele')[0]?.textContent?.trim();
          const eleParsed = el !== undefined && el !== '' ? parseFloat(el) : NaN;
          if (Number.isFinite(eleParsed)) {
            out.push([lat, lng, eleParsed]);
          } else {
            out.push([lat, lng]);
          }
        }
        return out;
      };
      const trk = fromPts('trkpt');
      if (trk.length >= 2) {
        return trk;
      }
      return fromPts('rtept');
    } catch {
      return [];
    }
  }

  private async loadEditData(clubId: number, id: number): Promise<void> {
    const a = await this.service.getById(clubId, id);
    if (!a) return;

    const modo: ModoCreacion =
      a.modo_creacion === 'importada' ? 'importada' : 'dibujada';

    this.form.patchValue(
      {
        titulo: a.titulo,
        descripcion: a.descripcion ?? '',
        fecha_inicio: a.fecha_inicio?.slice(0, 16) ?? '',
        fecha_fin: a.fecha_fin?.slice(0, 16) ?? '',
        lugar: a.lugar ?? '',
        punto_encuentro: a.punto_encuentro ?? '',
        material_necesario: a.material_necesario ?? '',
        modalidad: a.modalidad ?? '',
        dificultad: a.dificultad ?? '',
        cupo_maximo: a.cupo_maximo,
        costo: a.costo,
        modo_creacion: modo,
      },
      { emitEvent: false },
    );
    this.modoActual.set(modo);
    this.guiaUserIdsSeleccionados.set(a.guias?.map((g) => g.id) ?? []);

    if (a.track_geojson) {
      const coords = this.geoJsonToPoints(a.track_geojson);
      this.trackPoints.set(coords);
      const L = await import('leaflet');
      if (this.polyline) {
        this.polyline.setLatLngs(coords.map((p) => L.latLng(p[0], p[1])));
        const bounds = this.polyline.getBounds?.();
        if (bounds && bounds.isValid?.()) this.map.fitBounds(bounds);
      }
    } else {
      this.trackPoints.set([]);
      this.polyline?.setLatLngs([]);
    }
  }

  private geoJsonToPoints(g: GeoJSON.GeoJSON): Coord[] {
    const out: Coord[] = [];
    const visit = (geom: any) => {
      if (!geom) return;
      if (geom.type === 'LineString') {
        for (const c of geom.coordinates) {
          if (c.length > 2 && typeof c[2] === 'number') {
            out.push([c[1], c[0], c[2]]);
          } else {
            out.push([c[1], c[0]]);
          }
        }
      } else if (geom.type === 'MultiLineString') {
        for (const line of geom.coordinates) {
          for (const c of line) {
            if (c.length > 2 && typeof c[2] === 'number') {
              out.push([c[1], c[0], c[2]]);
            } else {
              out.push([c[1], c[0]]);
            }
          }
        }
      } else if (geom.type === 'Feature') {
        visit(geom.geometry);
      } else if (geom.type === 'FeatureCollection') {
        for (const f of geom.features) visit(f.geometry);
      }
    };
    visit(g);
    return out;
  }

  private buildTrackGeoJson(): GeoJSON.GeoJSON | null {
    const pts = this.trackPoints();
    if (pts.length < 2) return null;
    const coordinates = pts.map((p) => {
      const c: number[] = [p[1], p[0]];
      if (p.length > 2) {
        c.push(p[2]!);
      }
      return c as [number, number] | [number, number, number];
    });
    return {
      type: 'Feature',
      geometry: {
        type: 'LineString',
        coordinates,
      },
      properties: {},
    };
  }

  private async loadGuiasDelClub(clubId: number): Promise<void> {
    try {
      const first = await this.socios.fetchPage(clubId, { guide: true, page: 1 });
      const all: Socio[] = [...first.data];
      for (let p = 2; p <= first.last_page; p++) {
        const next = await this.socios.fetchPage(clubId, { guide: true, page: p });
        all.push(...next.data);
      }
      this.guiasDisponibles.set(all);
    } catch {
      this.guiasDisponibles.set([]);
    }
  }

  protected toggleGuiaUsuario(userId: number, checked: boolean): void {
    const cur = this.guiaUserIdsSeleccionados();
    if (checked) {
      if (cur.includes(userId)) {
        return;
      }
      this.guiaUserIdsSeleccionados.set([...cur, userId]);
    } else {
      this.guiaUserIdsSeleccionados.set(cur.filter((id) => id !== userId));
    }
  }

  protected isGuiaSelected(userId: number): boolean {
    return this.guiaUserIdsSeleccionados().includes(userId);
  }

  protected fieldError(name: string): string | null {
    const ctrl = this.form.get(name);
    if (!ctrl?.touched && !ctrl?.dirty) return null;
    if (!ctrl?.errors) return null;
    if (ctrl.errors['required']) return 'Campo obligatorio.';
    if (ctrl.errors['maxlength']) return 'Demasiado largo.';
    return null;
  }

  protected async submit(): Promise<void> {
    const c = this.club();
    if (!c) return;
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    const track = this.buildTrackGeoJson();
    if (!track) {
      this.serverError.set(
        'Dibuja al menos dos puntos en el mapa o importa un GPX con una ruta válida.',
      );
      return;
    }

    this.submitting.set(true);
    this.serverError.set(null);

    const v = this.form.getRawValue();
    const dist = this.trackDistanceKm();
    const guia_ids = this.guiaUserIdsSeleccionados();
    const base: CreateActividadRequest = {
      titulo: v.titulo,
      descripcion: v.descripcion || null,
      fecha_inicio: v.fecha_inicio,
      fecha_fin: v.fecha_fin || null,
      lugar: v.lugar || null,
      punto_encuentro: v.punto_encuentro || null,
      material_necesario: v.material_necesario || null,
      modalidad: v.modalidad || null,
      distancia: dist !== null ? dist : null,
      dificultad: v.dificultad || null,
      cupo_maximo: v.cupo_maximo ? Number(v.cupo_maximo) : null,
      costo: v.costo !== null && v.costo !== '' ? Number(v.costo) : null,
      modo_creacion: v.modo_creacion as ModoCreacion,
      track_geojson: track,
    };
    const id = this.editId();

    try {
      let result: Actividad;
      if (id) {
        const updatePayload: UpdateActividadRequest = { ...base, guia_ids };
        result = await this.service.update(c.id, id, updatePayload);
        this.toast.success('Actividad actualizada.');
      } else {
        const createPayload: CreateActividadRequest =
          guia_ids.length > 0 ? { ...base, guia_ids } : base;
        result = await this.service.create(c.id, createPayload);
        this.toast.success('Actividad creada.');
      }
      this.router.navigate(['/clubes', c.id, 'actividades', result.id]);
    } catch (err) {
      const apiErr = toApiError(err);
      const firstField = apiErr.errors ? Object.values(apiErr.errors).flat()[0] : null;
      this.serverError.set(firstField ?? apiErr.message);
    } finally {
      this.submitting.set(false);
    }
  }
}

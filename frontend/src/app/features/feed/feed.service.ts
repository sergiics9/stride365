import { HttpClient, HttpParams } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import { Actividad, ApiError, Paginated, PublicacionFeed } from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

export interface FeedFilters {
  tipo: string | null;
  dificultad: string | null;
  desde: string | null;
  hasta: string | null;
}

export const EMPTY_FILTERS: FeedFilters = {
  tipo: null,
  dificultad: null,
  desde: null,
  hasta: null,
};

@Injectable({ providedIn: 'root' })
export class FeedService {
  private readonly http = inject(HttpClient);

  private readonly _list = signal<Paginated<PublicacionFeed> | null>(null);
  private readonly _listLoading = signal<boolean>(false);
  private readonly _listError = signal<ApiError | null>(null);

  readonly list = this._list.asReadonly();
  readonly listLoading = this._listLoading.asReadonly();
  readonly listError = this._listError.asReadonly();

  readonly publicaciones = computed(() => this._list()?.data ?? []);
  readonly currentPage = computed(() => this._list()?.current_page ?? 1);
  readonly lastPage = computed(() => this._list()?.last_page ?? 1);
  readonly total = computed(() => this._list()?.total ?? 0);

  async loadList(
    filters: FeedFilters,
    page = 1,
    perPage = 12,
  ): Promise<Paginated<PublicacionFeed> | null> {
    this._listLoading.set(true);
    this._listError.set(null);

    let params = new HttpParams().set('page', page).set('per_page', perPage);

    if (filters.tipo) params = params.set('tipo', filters.tipo);
    if (filters.dificultad) params = params.set('dificultad', filters.dificultad);
    if (filters.desde) params = params.set('desde', filters.desde);
    if (filters.hasta) params = params.set('hasta', filters.hasta);

    try {
      const response = await firstValueFrom(
        this.http.get<Paginated<PublicacionFeed>>(`${environment.apiUrl}/feed`, { params }),
      );
      this._list.set(response);
      return response;
    } catch (error) {
      this._listError.set(toApiError(error));
      return null;
    } finally {
      this._listLoading.set(false);
    }
  }

  async getById(id: number | string): Promise<PublicacionFeed> {
    return firstValueFrom(this.http.get<PublicacionFeed>(`${environment.apiUrl}/feed/${id}`));
  }

  async startRecording(body: { titulo?: string } = {}): Promise<Actividad> {
    return await firstValueFrom(
      this.http.post<Actividad>(`${environment.apiUrl}/feed/recordings/start`, body),
    );
  }

  async appendRecordingCoords(recordingId: number, coordinates: number[][]): Promise<Actividad> {
    return await firstValueFrom(
      this.http.patch<Actividad>(`${environment.apiUrl}/feed/recordings/${recordingId}`, {
        coordinates,
      }),
    );
  }

  async finishRecording(
    recordingId: number,
    body: { titulo?: string; descripcion?: string } = {},
  ): Promise<{ message: string; actividad: Actividad }> {
    return await firstValueFrom(
      this.http.post<{ message: string; actividad: Actividad }>(
        `${environment.apiUrl}/feed/recordings/${recordingId}/finish`,
        body,
      ),
    );
  }

  async importGpx(file: File, titulo?: string): Promise<{ message: string; actividad: Actividad }> {
    const fd = new FormData();
    fd.append('gpx', file);
    if (titulo?.trim()) {
      fd.append('titulo', titulo.trim());
    }
    return await firstValueFrom(
      this.http.post<{ message: string; actividad: Actividad }>(
        `${environment.apiUrl}/feed/recordings/import-gpx`,
        fd,
      ),
    );
  }
}

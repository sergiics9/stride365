import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import {
  Actividad,
  ActividadEstado,
  ApiError,
  CreateActividadRequest,
  Inscripcion,
  Paginated,
  UpdateActividadRequest,
} from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

@Injectable({ providedIn: 'root' })
export class ActividadesService {
  private readonly http = inject(HttpClient);

  private readonly _list = signal<Paginated<Actividad> | null>(null);
  private readonly _loading = signal(false);
  private readonly _error = signal<ApiError | null>(null);
  private readonly _saving = signal(false);

  readonly list = this._list.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly saving = this._saving.asReadonly();

  async load(clubId: number, params: { estado?: ActividadEstado; page?: number } = {}): Promise<void> {
    this._loading.set(true);
    this._error.set(null);
    try {
      const data = await firstValueFrom(
        this.http.get<Paginated<Actividad>>(
          `${environment.apiUrl}/clubes/${clubId}/actividades`,
          { params: this.cleanParams(params) },
        ),
      );
      this._list.set(data);
    } catch (err) {
      this._error.set(toApiError(err));
    } finally {
      this._loading.set(false);
    }
  }

  async getById(clubId: number, id: number): Promise<Actividad | null> {
    try {
      return await firstValueFrom(
        this.http.get<Actividad>(`${environment.apiUrl}/clubes/${clubId}/actividades/${id}`),
      );
    } catch {
      return null;
    }
  }

  async create(clubId: number, payload: CreateActividadRequest): Promise<Actividad> {
    this._saving.set(true);
    try {
      return await firstValueFrom(
        this.http.post<Actividad>(
          `${environment.apiUrl}/clubes/${clubId}/actividades`,
          payload,
        ),
      );
    } finally {
      this._saving.set(false);
    }
  }

  async update(clubId: number, id: number, payload: UpdateActividadRequest): Promise<Actividad> {
    this._saving.set(true);
    try {
      return await firstValueFrom(
        this.http.put<Actividad>(
          `${environment.apiUrl}/clubes/${clubId}/actividades/${id}`,
          payload,
        ),
      );
    } finally {
      this._saving.set(false);
    }
  }

  async cancel(clubId: number, id: number, motivo?: string): Promise<void> {
    await firstValueFrom(
      this.http.delete(`${environment.apiUrl}/clubes/${clubId}/actividades/${id}`, {
        body: { motivo_cancelacion: motivo },
      }),
    );
  }

  async finish(
    clubId: number,
    id: number,
    payload: { titulo?: string; descripcion?: string } = {},
  ): Promise<{ message: string; actividad: Actividad }> {
    return await firstValueFrom(
      this.http.post<{ message: string; actividad: Actividad }>(
        `${environment.apiUrl}/clubes/${clubId}/actividades/${id}/finish`,
        payload,
      ),
    );
  }

  async listInscripciones(actividadId: number): Promise<Paginated<Inscripcion>> {
    return await firstValueFrom(
      this.http.get<Paginated<Inscripcion>>(
        `${environment.apiUrl}/actividades/${actividadId}/inscripciones`,
      ),
    );
  }

  async inscribir(actividadId: number, userId?: number): Promise<Inscripcion> {
    return await firstValueFrom(
      this.http.post<Inscripcion>(
        `${environment.apiUrl}/actividades/${actividadId}/inscripciones`,
        userId ? { user_id: userId } : {},
      ),
    );
  }

  async cancelarInscripcion(actividadId: number, inscripcionId: number, motivo?: string): Promise<void> {
    await firstValueFrom(
      this.http.delete(
        `${environment.apiUrl}/actividades/${actividadId}/inscripciones/${inscripcionId}`,
        { body: { motivo } },
      ),
    );
  }

  private cleanParams(input: Record<string, unknown>): Record<string, string> {
    return Object.entries(input).reduce<Record<string, string>>((acc, [k, v]) => {
      if (v !== undefined && v !== null && v !== '') {
        acc[k] = String(v);
      }
      return acc;
    }, {});
  }
}

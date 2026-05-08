import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import {
  ApiError,
  Comunicado,
  CreateComunicadoRequest,
  Paginated,
} from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

@Injectable({ providedIn: 'root' })
export class ComunicadosService {
  private readonly http = inject(HttpClient);

  private readonly _list = signal<Paginated<Comunicado> | null>(null);
  private readonly _loading = signal(false);
  private readonly _error = signal<ApiError | null>(null);
  private readonly _saving = signal(false);

  readonly list = this._list.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly saving = this._saving.asReadonly();

  async load(clubId: number, page = 1): Promise<void> {
    this._loading.set(true);
    this._error.set(null);
    try {
      const data = await firstValueFrom(
        this.http.get<Paginated<Comunicado>>(
          `${environment.apiUrl}/clubes/${clubId}/comunicados`,
          { params: { page: String(page) } },
        ),
      );
      this._list.set(data);
    } catch (err) {
      this._error.set(toApiError(err));
    } finally {
      this._loading.set(false);
    }
  }

  async create(clubId: number, payload: CreateComunicadoRequest): Promise<Comunicado> {
    this._saving.set(true);
    try {
      return await firstValueFrom(
        this.http.post<Comunicado>(
          `${environment.apiUrl}/clubes/${clubId}/comunicados`,
          payload,
        ),
      );
    } finally {
      this._saving.set(false);
    }
  }

  async destroy(clubId: number, id: number): Promise<void> {
    await firstValueFrom(
      this.http.delete(`${environment.apiUrl}/clubes/${clubId}/comunicados/${id}`),
    );
  }
}

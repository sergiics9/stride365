import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import {
  ApiError,
  CreateSocioRequest,
  Paginated,
  Socio,
  UpdateSocioRequest,
} from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

interface ListParams {
  q?: string;
  estado?: string;
  guide?: boolean;
  page?: number;
}

@Injectable({ providedIn: 'root' })
export class SociosService {
  private readonly http = inject(HttpClient);

  private readonly _list = signal<Paginated<Socio> | null>(null);
  private readonly _loading = signal(false);
  private readonly _error = signal<ApiError | null>(null);
  private readonly _saving = signal(false);

  readonly list = this._list.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly saving = this._saving.asReadonly();

  
  async fetchPage(clubId: number, params: ListParams = {}): Promise<Paginated<Socio>> {
    return await firstValueFrom(
      this.http.get<Paginated<Socio>>(`${environment.apiUrl}/clubes/${clubId}/socios`, {
        params: this.cleanParams(params as Record<string, unknown>),
      }),
    );
  }

  async load(clubId: number, params: ListParams = {}): Promise<void> {
    this._loading.set(true);
    this._error.set(null);
    try {
      const data = await firstValueFrom(
        this.http.get<Paginated<Socio>>(`${environment.apiUrl}/clubes/${clubId}/socios`, {
          params: this.cleanParams(params as Record<string, unknown>),
        }),
      );
      this._list.set(data);
    } catch (err) {
      this._error.set(toApiError(err));
    } finally {
      this._loading.set(false);
    }
  }

  async create(clubId: number, payload: CreateSocioRequest): Promise<Socio> {
    this._saving.set(true);
    try {
      return await firstValueFrom(
        this.http.post<Socio>(`${environment.apiUrl}/clubes/${clubId}/socios`, payload),
      );
    } finally {
      this._saving.set(false);
    }
  }

  async update(clubId: number, socioId: number, payload: UpdateSocioRequest): Promise<Socio> {
    this._saving.set(true);
    try {
      return await firstValueFrom(
        this.http.put<Socio>(`${environment.apiUrl}/clubes/${clubId}/socios/${socioId}`, payload),
      );
    } finally {
      this._saving.set(false);
    }
  }

  async destroy(clubId: number, socioId: number, motivo?: string): Promise<void> {
    await firstValueFrom(
      this.http.delete(`${environment.apiUrl}/clubes/${clubId}/socios/${socioId}`, {
        body: { motivo },
      }),
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

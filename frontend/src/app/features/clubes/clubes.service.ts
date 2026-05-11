import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import {
  ApiError,
  Club,
  ClubApplication,
  CreateClubApplicationPayload,
  Paginated,
} from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

@Injectable({ providedIn: 'root' })
export class ClubesService {
  private readonly http = inject(HttpClient);

  private readonly _list = signal<Paginated<Club> | null>(null);
  private readonly _listLoading = signal(false);
  private readonly _listError = signal<ApiError | null>(null);

  readonly list = this._list.asReadonly();
  readonly listLoading = this._listLoading.asReadonly();
  readonly listError = this._listError.asReadonly();

  async loadList(params?: { q?: string; page?: number }): Promise<Paginated<Club> | null> {
    this._listLoading.set(true);
    this._listError.set(null);
    try {
      const data = await firstValueFrom(
        this.http.get<Paginated<Club>>(`${environment.apiUrl}/clubes`, {
          params: this.cleanParams({
            q: params?.q,
            page: params?.page,
          }),
        }),
      );
      this._list.set(data);
      return data;
    } catch (err) {
      this._listError.set(toApiError(err));
      return null;
    } finally {
      this._listLoading.set(false);
    }
  }

  async getById(id: number): Promise<Club | null> {
    try {
      return await firstValueFrom(
        this.http.get<Club>(`${environment.apiUrl}/clubes/${id}`),
      );
    } catch {
      return null;
    }
  }

  async delete(id: number): Promise<void> {
    await firstValueFrom(this.http.delete(`${environment.apiUrl}/clubes/${id}`));
  }

  async createApplication(payload: CreateClubApplicationPayload): Promise<ClubApplication> {
    const fd = new FormData();
    fd.append('nombre', payload.nombre);
    if (payload.descripcion != null && payload.descripcion !== '') {
      fd.append('descripcion', payload.descripcion);
    }
    if (payload.direccion != null && payload.direccion !== '') {
      fd.append('direccion', payload.direccion);
    }
    if (payload.telefono != null && payload.telefono !== '') {
      fd.append('telefono', payload.telefono);
    }
    if (payload.email != null && payload.email !== '') {
      fd.append('email', payload.email);
    }
    if (payload.logo) {
      fd.append('logo', payload.logo, payload.logo.name);
    }

    return await firstValueFrom(
      this.http.post<ClubApplication>(`${environment.apiUrl}/clubs/applications`, fd),
    );
  }

  private cleanParams(input: Record<string, string | number | undefined>): Record<string, string> {
    return Object.entries(input).reduce<Record<string, string>>((acc, [k, v]) => {
      if (v !== undefined && v !== null && v !== '') {
        acc[k] = String(v);
      }
      return acc;
    }, {});
  }
}

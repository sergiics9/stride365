import { HttpClient } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import { ApiError, ClubApplication, ClubApplicationStatus, Paginated } from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

@Injectable({ providedIn: 'root' })
export class ClubApplicationsService {
  private readonly http = inject(HttpClient);

  private readonly _list = signal<Paginated<ClubApplication> | null>(null);
  private readonly _loading = signal(false);
  private readonly _error = signal<ApiError | null>(null);
  private readonly _action = signal<'approve' | 'reject' | null>(null);
  private readonly _actionTarget = signal<number | null>(null);

  readonly list = this._list.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();
  readonly action = this._action.asReadonly();
  readonly actionTarget = this._actionTarget.asReadonly();

  async load(status: ClubApplicationStatus | '' = 'pending', page = 1): Promise<void> {
    this._loading.set(true);
    this._error.set(null);
    try {
      const data = await firstValueFrom(
        this.http.get<Paginated<ClubApplication>>(`${environment.apiUrl}/clubs/applications`, {
          params: status ? { status, page: String(page) } : { page: String(page) },
        }),
      );
      this._list.set(data);
    } catch (err) {
      this._error.set(toApiError(err));
    } finally {
      this._loading.set(false);
    }
  }

  async approve(clubId: number): Promise<void> {
    this._action.set('approve');
    this._actionTarget.set(clubId);
    try {
      await firstValueFrom(
        this.http.post(`${environment.apiUrl}/clubs/applications/${clubId}/approve`, {}),
      );
    } finally {
      this._action.set(null);
      this._actionTarget.set(null);
    }
  }

  async reject(clubId: number, reason: string): Promise<void> {
    this._action.set('reject');
    this._actionTarget.set(clubId);
    try {
      await firstValueFrom(
        this.http.post(`${environment.apiUrl}/clubs/applications/${clubId}/reject`, { reason }),
      );
    } finally {
      this._action.set(null);
      this._actionTarget.set(null);
    }
  }
}

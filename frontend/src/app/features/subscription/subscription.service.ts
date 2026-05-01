import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import { AuthService } from '../../core/auth/auth.service';
import { ApiError, SubscriptionStatus } from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

@Injectable({ providedIn: 'root' })
export class SubscriptionService {
  private readonly http = inject(HttpClient);
  private readonly auth = inject(AuthService);

  private readonly _status = signal<SubscriptionStatus | null>(null);
  private readonly _loading = signal<boolean>(false);
  private readonly _error = signal<ApiError | null>(null);

  readonly status = this._status.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();

  readonly hasActiveSubscription = computed(
    () => this._status()?.subscribed ?? this.auth.subscribed(),
  );

  async loadStatus(force = false): Promise<SubscriptionStatus | null> {
    if (!force && this._status() !== null) {
      return this._status();
    }

    this._loading.set(true);
    this._error.set(null);
    try {
      const status = await firstValueFrom(
        this.http.get<SubscriptionStatus>(`${environment.apiUrl}/subscription/status`),
      );
      this._status.set(status);
      this.auth.setSubscribed(status.subscribed);
      return status;
    } catch (error) {
      this._error.set(toApiError(error));
      return null;
    } finally {
      this._loading.set(false);
    }
  }

  reset(): void {
    this._status.set(null);
    this._error.set(null);
  }
}

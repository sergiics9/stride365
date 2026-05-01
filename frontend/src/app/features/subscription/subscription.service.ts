import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import { AuthService } from '../../core/auth/auth.service';
import {
  ApiError,
  CheckoutRequest,
  CheckoutSession,
  Invoice,
  SubscriptionStatus,
} from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

@Injectable({ providedIn: 'root' })
export class SubscriptionService {
  private readonly http = inject(HttpClient);
  private readonly auth = inject(AuthService);

  private readonly _status = signal<SubscriptionStatus | null>(null);
  private readonly _loading = signal<boolean>(false);
  private readonly _error = signal<ApiError | null>(null);

  private readonly _invoices = signal<Invoice[]>([]);
  private readonly _invoicesLoading = signal<boolean>(false);
  private readonly _invoicesError = signal<ApiError | null>(null);

  private readonly _action = signal<'checkout' | 'cancel' | 'resume' | null>(null);
  private readonly _downloadingInvoice = signal<string | null>(null);

  readonly status = this._status.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();

  readonly invoices = this._invoices.asReadonly();
  readonly invoicesLoading = this._invoicesLoading.asReadonly();
  readonly invoicesError = this._invoicesError.asReadonly();

  readonly action = this._action.asReadonly();
  readonly downloadingInvoice = this._downloadingInvoice.asReadonly();

  readonly hasActiveSubscription = computed(
    () => this._status()?.subscribed ?? this.auth.subscribed(),
  );

  readonly canCancel = computed(() => {
    const s = this._status();
    return !!s?.subscribed && !s.on_grace_period && !s.cancelled;
  });

  readonly canResume = computed(() => this._status()?.on_grace_period === true);

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

  async checkout(payload: CheckoutRequest): Promise<CheckoutSession | null> {
    this._action.set('checkout');
    try {
      return await firstValueFrom(
        this.http.post<CheckoutSession>(`${environment.apiUrl}/subscription/checkout`, payload),
      );
    } finally {
      this._action.set(null);
    }
  }

  async cancel(): Promise<{ message: string; ends_at: string | null }> {
    this._action.set('cancel');
    try {
      const result = await firstValueFrom(
        this.http.post<{ message: string; ends_at: string | null }>(
          `${environment.apiUrl}/subscription/cancel`,
          {},
        ),
      );
      await this.loadStatus(true);
      return result;
    } finally {
      this._action.set(null);
    }
  }

  async resume(): Promise<{ message: string }> {
    this._action.set('resume');
    try {
      const result = await firstValueFrom(
        this.http.post<{ message: string }>(`${environment.apiUrl}/subscription/resume`, {}),
      );
      await this.loadStatus(true);
      return result;
    } finally {
      this._action.set(null);
    }
  }

  async loadInvoices(): Promise<Invoice[]> {
    this._invoicesLoading.set(true);
    this._invoicesError.set(null);
    try {
      const list = await firstValueFrom(
        this.http.get<Invoice[]>(`${environment.apiUrl}/subscription/invoices`),
      );
      this._invoices.set(list);
      return list;
    } catch (error) {
      this._invoicesError.set(toApiError(error));
      return [];
    } finally {
      this._invoicesLoading.set(false);
    }
  }

  async downloadInvoice(invoice: Invoice): Promise<void> {
    this._downloadingInvoice.set(invoice.id);
    try {
      const blob = await firstValueFrom(
        this.http.get(`${environment.apiUrl}/subscription/invoices/${invoice.id}`, {
          responseType: 'blob',
        }),
      );
      const url = URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.download = `factura-${invoice.number ?? invoice.id}.pdf`;
      document.body.appendChild(link);
      link.click();
      link.remove();
      URL.revokeObjectURL(url);
    } finally {
      this._downloadingInvoice.set(null);
    }
  }

  reset(): void {
    this._status.set(null);
    this._error.set(null);
    this._invoices.set([]);
    this._invoicesError.set(null);
  }
}

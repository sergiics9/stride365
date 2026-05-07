import { HttpClient } from '@angular/common/http';
import { Injectable, computed, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';

import { environment } from '../../../environments/environment';
import { AuthService } from '../../core/auth/auth.service';
import {
  ApiError,
  CancelResumeRequest,
  CheckoutRequest,
  CheckoutSession,
  Invoice,
  Membership,
  MembershipKind,
  MembershipsResponse,
  SubscriptionStatus,
} from '../../shared/models';
import { toApiError } from '../../shared/utils/api-error.util';

type Action = 'checkout' | 'cancel' | 'resume' | null;

@Injectable({ providedIn: 'root' })
export class MembershipsService {
  private readonly http = inject(HttpClient);
  private readonly auth = inject(AuthService);

  private readonly _memberships = signal<Membership[]>([]);
  private readonly _loading = signal<boolean>(false);
  private readonly _error = signal<ApiError | null>(null);

  private readonly _invoices = signal<Invoice[]>([]);
  private readonly _invoicesLoading = signal<boolean>(false);
  private readonly _invoicesError = signal<ApiError | null>(null);

  private readonly _action = signal<Action>(null);
  private readonly _actionTarget = signal<string | null>(null);
  private readonly _downloadingInvoice = signal<string | null>(null);

  readonly memberships = this._memberships.asReadonly();
  readonly loading = this._loading.asReadonly();
  readonly error = this._error.asReadonly();

  readonly invoices = this._invoices.asReadonly();
  readonly invoicesLoading = this._invoicesLoading.asReadonly();
  readonly invoicesError = this._invoicesError.asReadonly();

  readonly action = this._action.asReadonly();
  readonly actionTarget = this._actionTarget.asReadonly();
  readonly downloadingInvoice = this._downloadingInvoice.asReadonly();

  readonly adminMembership = computed<Membership | null>(
    () => this._memberships().find((m) => m.role === 'admin_club') ?? null,
  );

  readonly socioMemberships = computed<Membership[]>(() =>
    this._memberships().filter((m) => m.role === 'socio'),
  );

  invoicesFor(name: string): Invoice[] {
    return this._invoices().filter((i) => i.subscription?.name === name);
  }

  async loadMemberships(force = false): Promise<Membership[]> {
    if (!force && this._memberships().length > 0) {
      return this._memberships();
    }

    this._loading.set(true);
    this._error.set(null);
    try {
      const response = await firstValueFrom(
        this.http.get<MembershipsResponse>(`${environment.apiUrl}/subscription/memberships`),
      );
      this._memberships.set(response.memberships ?? []);
      this.auth.setMemberships(response.memberships ?? []);
      return response.memberships ?? [];
    } catch (err) {
      this._error.set(toApiError(err));
      return [];
    } finally {
      this._loading.set(false);
    }
  }

  async getStatus(kind: MembershipKind, clubId: number): Promise<SubscriptionStatus | null> {
    try {
      return await firstValueFrom(
        this.http.get<SubscriptionStatus>(`${environment.apiUrl}/subscription/status`, {
          params: { kind, club_id: String(clubId) },
        }),
      );
    } catch {
      return null;
    }
  }

  async checkout(payload: CheckoutRequest): Promise<CheckoutSession | null> {
    this._action.set('checkout');
    this._actionTarget.set(`${payload.kind}-${payload.club_id}`);
    try {
      return await firstValueFrom(
        this.http.post<CheckoutSession>(`${environment.apiUrl}/subscription/checkout`, payload),
      );
    } finally {
      this._action.set(null);
      this._actionTarget.set(null);
    }
  }

  async cancel(payload: CancelResumeRequest): Promise<{ message: string }> {
    this._action.set('cancel');
    this._actionTarget.set(`${payload.kind}-${payload.club_id}`);
    try {
      const result = await firstValueFrom(
        this.http.post<{ message: string }>(
          `${environment.apiUrl}/subscription/cancel`,
          payload,
        ),
      );
      await this.loadMemberships(true);
      return result;
    } finally {
      this._action.set(null);
      this._actionTarget.set(null);
    }
  }

  async resume(payload: CancelResumeRequest): Promise<{ message: string }> {
    this._action.set('resume');
    this._actionTarget.set(`${payload.kind}-${payload.club_id}`);
    try {
      const result = await firstValueFrom(
        this.http.post<{ message: string }>(
          `${environment.apiUrl}/subscription/resume`,
          payload,
        ),
      );
      await this.loadMemberships(true);
      return result;
    } finally {
      this._action.set(null);
      this._actionTarget.set(null);
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
    } catch (err) {
      this._invoicesError.set(toApiError(err));
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
    this._memberships.set([]);
    this._error.set(null);
    this._invoices.set([]);
    this._invoicesError.set(null);
  }
}

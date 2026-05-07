import { DatePipe, Location } from '@angular/common';
import {
  ChangeDetectionStrategy,
  Component,
  computed,
  effect,
  inject,
  signal,
} from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { map } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/auth/auth.service';
import { ToastService } from '../../../core/toast/toast.service';
import { Invoice, Membership, MembershipKind } from '../../../shared/models';
import { toApiError } from '../../../shared/utils/api-error.util';
import { MembershipsService } from '../memberships.service';

@Component({
  selector: 'app-memberships-overview',
  imports: [DatePipe, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './memberships-overview.component.html',
  styleUrl: './memberships-overview.component.scss',
})
export class MembershipsOverviewComponent {
  protected readonly auth = inject(AuthService);
  protected readonly service = inject(MembershipsService);
  private readonly toast = inject(ToastService);
  private readonly location = inject(Location);
  private readonly route = inject(ActivatedRoute);

  protected readonly pricing = environment.pricing;

  protected readonly memberships = this.service.memberships;
  protected readonly loading = this.service.loading;
  protected readonly error = this.service.error;
  protected readonly invoices = this.service.invoices;
  protected readonly invoicesLoading = this.service.invoicesLoading;
  protected readonly invoicesError = this.service.invoicesError;
  protected readonly action = this.service.action;
  protected readonly actionTarget = this.service.actionTarget;
  protected readonly downloadingInvoice = this.service.downloadingInvoice;

  protected readonly adminMembership = this.service.adminMembership;
  protected readonly socioMemberships = this.service.socioMemberships;

  protected readonly checkoutSuccess = signal<{ kind: MembershipKind; clubId: number } | null>(null);

  protected readonly noticeReason = toSignal(
    this.route.queryParamMap.pipe(map((p) => p.get('reason'))),
    { initialValue: null as string | null },
  );

  protected readonly notice = computed(() =>
    this.noticeReason() === 'required'
      ? 'Necesitas una suscripción activa para acceder al módulo Clubes.'
      : null,
  );

  private readonly checkoutOutcome = toSignal(
    this.route.queryParamMap.pipe(
      map((p) => ({
        status: p.get('status'),
        kind: p.get('kind') as MembershipKind | null,
        clubId: p.get('club_id') ? Number(p.get('club_id')) : null,
        sessionId: p.get('session_id'),
      })),
    ),
    {
      initialValue: {
        status: null as string | null,
        kind: null as MembershipKind | null,
        clubId: null as number | null,
        sessionId: null as string | null,
      },
    },
  );

  constructor() {
    effect(() => {
      const { status, kind, clubId, sessionId } = this.checkoutOutcome();

      if (status === 'success' && sessionId) {
        if (kind && clubId) {
          this.checkoutSuccess.set({ kind, clubId });
        }
        this.toast.success(
          '¡Suscripción completada! Recibirás la factura en tu email.',
          10000,
        );
        void this.service
          .loadMemberships(true)
          .then(() => this.service.loadInvoices());
        this.clearQueryParams();
      } else if (status === 'cancel') {
        this.toast.info('Has cancelado el proceso de suscripción.');
        this.clearQueryParams();
      }
    });

    void this.service.loadInvoices();
  }

  protected reload(): void {
    void this.service.loadMemberships(true);
    void this.service.loadInvoices();
  }

  protected statusBadge(m: Membership): { label: string; cls: string } {
    switch (m.status) {
      case 'active':
        return { label: 'Activa', cls: 'bg-success' };
      case 'grace':
        return { label: 'En periodo de gracia', cls: 'bg-warning text-dark' };
      case 'pending':
        return { label: 'Pendiente de pago', cls: 'bg-secondary' };
      case 'cancelled':
        return { label: 'Cancelada', cls: 'bg-danger' };
      case 'inactive':
      default:
        return { label: 'Inactiva', cls: 'bg-secondary' };
    }
  }

  protected canCancel(m: Membership): boolean {
    return m.status === 'active';
  }

  protected canResume(m: Membership): boolean {
    return m.status === 'grace';
  }

  protected isActing(kind: MembershipKind, clubId: number): boolean {
    return this.actionTarget() === `${kind}-${clubId}`;
  }

  protected nextRenewal(m: Membership): string | null {
    if (m.status === 'cancelled' || m.status === 'grace') return m.ends_at;
    return m.current_period_end ?? null;
  }

  protected nextRenewalLabel(m: Membership): string {
    if (m.status === 'cancelled' || m.status === 'grace') return 'Acceso hasta';
    return 'Próximo cobro';
  }

  protected invoicesFor(m: Membership): Invoice[] {
    return this.invoices().filter((i) => i.subscription?.club_id === m.club_id);
  }

  protected async cancelMembership(m: Membership): Promise<void> {
    const noun = m.role === 'admin_club' ? 'tu club' : `tu suscripción de socio (${m.club?.nombre ?? 'club'})`;
    if (!confirm(`¿Seguro que quieres cancelar ${noun} al final del periodo actual?`)) {
      return;
    }
    try {
      const result = await this.service.cancel({ kind: m.kind, club_id: m.club_id });
      this.toast.success(result.message);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected async resumeMembership(m: Membership): Promise<void> {
    try {
      const result = await this.service.resume({ kind: m.kind, club_id: m.club_id });
      this.toast.success(result.message);
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected async download(invoice: Invoice): Promise<void> {
    try {
      await this.service.downloadInvoice(invoice);
    } catch (err) {
      this.toast.error('No se pudo descargar la factura: ' + toApiError(err).message);
    }
  }

  protected reloadInvoices(): void {
    void this.service.loadInvoices();
  }

  private clearQueryParams(): void {
    this.location.replaceState('/memberships');
  }
}

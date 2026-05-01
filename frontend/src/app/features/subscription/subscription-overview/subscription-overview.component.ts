import { DatePipe, Location } from '@angular/common';
import {
  ChangeDetectionStrategy,
  Component,
  computed,
  effect,
  inject,
  input,
  signal,
} from '@angular/core';
import { toSignal } from '@angular/core/rxjs-interop';
import { ActivatedRoute, RouterLink } from '@angular/router';
import { map } from 'rxjs/operators';

import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/auth/auth.service';
import { ToastService } from '../../../core/toast/toast.service';
import { Invoice, StripePlan } from '../../../shared/models';
import { toApiError } from '../../../shared/utils/api-error.util';
import { SubscriptionService } from '../subscription.service';

@Component({
  selector: 'app-subscription-overview',
  imports: [DatePipe, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './subscription-overview.component.html',
  styleUrl: './subscription-overview.component.scss',
})
export class SubscriptionOverviewComponent {
  protected readonly auth = inject(AuthService);
  protected readonly subscription = inject(SubscriptionService);
  private readonly toast = inject(ToastService);
  private readonly location = inject(Location);
  private readonly route = inject(ActivatedRoute);

  readonly reason = input<string | null>(null);

  private readonly checkoutOutcome = toSignal(
    this.route.queryParamMap.pipe(
      map((params) => ({
        status: params.get('status'),
        sessionId: params.get('session_id'),
      })),
    ),
    { initialValue: { status: null as string | null, sessionId: null as string | null } },
  );

  protected readonly plans: StripePlan[] = environment.stripe.plans;
  protected readonly selectedPlanId = signal<string>(this.plans[0]?.id ?? '');

  protected readonly status = this.subscription.status;
  protected readonly loading = this.subscription.loading;
  protected readonly error = this.subscription.error;
  protected readonly invoices = this.subscription.invoices;
  protected readonly invoicesLoading = this.subscription.invoicesLoading;
  protected readonly invoicesError = this.subscription.invoicesError;
  protected readonly action = this.subscription.action;
  protected readonly downloadingInvoice = this.subscription.downloadingInvoice;

  protected readonly selectedPlan = computed<StripePlan | null>(
    () => this.plans.find((p) => p.id === this.selectedPlanId()) ?? null,
  );

  protected readonly badge = computed(() => {
    const s = this.status();
    if (!s) return { label: 'Sin datos', cls: 'bg-secondary' };
    if (s.subscribed && !s.cancelled && !s.on_grace_period)
      return { label: 'Activa', cls: 'bg-success' };
    if (s.on_grace_period) return { label: 'En periodo de gracia', cls: 'bg-warning text-dark' };
    if (s.on_trial) return { label: 'Periodo de prueba', cls: 'bg-info text-dark' };
    if (s.cancelled) return { label: 'Cancelada', cls: 'bg-danger' };
    return { label: 'Sin suscripción', cls: 'bg-secondary' };
  });

  protected readonly notice = computed(() =>
    this.reason() === 'required'
      ? 'Necesitas una suscripción activa para acceder al módulo Clubes.'
      : null,
  );

  protected readonly checkoutSuccess = signal(false);

  protected readonly currentPlan = computed<StripePlan | null>(() => {
    const priceId = this.status()?.stripe_price;
    if (!priceId) return null;
    return this.plans.find((p) => p.priceId === priceId) ?? null;
  });

  protected readonly planLabel = computed(() => {
    const plan = this.currentPlan();
    if (plan) return `${plan.name} — ${plan.amountLabel}`;
    return this.status()?.stripe_price ?? '—';
  });

  protected readonly nextRenewal = computed<string | null>(() => {
    const s = this.status();
    if (!s) return null;
    if (s.cancelled || s.on_grace_period) return s.ends_at;
    return s.current_period_end ?? null;
  });

  protected readonly nextRenewalLabel = computed(() => {
    const s = this.status();
    if (!s) return 'Vence el';
    if (s.cancelled || s.on_grace_period) return 'Acceso hasta';
    return 'Próximo cobro';
  });

  constructor() {
    effect(() => {
      const { status, sessionId } = this.checkoutOutcome();

      if (status === 'success' && sessionId) {
        this.checkoutSuccess.set(true);
        this.toast.success(
          '¡Suscripción completada! Recibirás la factura en tu email.',
          10000,
        );
        void this.subscription.loadStatus(true).then(() => this.subscription.loadInvoices());
        this.clearQueryParams();
      } else if (status === 'cancel') {
        this.toast.info('Has cancelado el proceso de suscripción.');
        this.clearQueryParams();
      }
    });

    void this.subscription.loadInvoices();
  }

  protected reload(): void {
    void this.subscription.loadStatus(true);
    void this.subscription.loadInvoices();
  }

  protected async startCheckout(): Promise<void> {
    const plan = this.selectedPlan();
    if (!plan) {
      this.toast.error('No hay plan seleccionado.');
      return;
    }
    if (plan.priceId.startsWith('price_REPLACE')) {
      this.toast.warning(
        'Configura un priceId real de Stripe en environment.ts antes de probar el checkout.',
      );
      return;
    }

    const baseUrl = window.location.origin + '/subscription';
    try {
      const checkout = await this.subscription.checkout({
        price_id: plan.priceId,
        success_url: `${baseUrl}?status=success`,
        cancel_url: `${baseUrl}?status=cancel`,
      });
      if (checkout?.url) {
        window.location.href = checkout.url;
      }
    } catch (error) {
      this.toast.error(toApiError(error).message);
    }
  }

  protected async cancelSubscription(): Promise<void> {
    if (!confirm('¿Seguro que quieres cancelar la suscripción al final del periodo actual?')) {
      return;
    }
    try {
      const result = await this.subscription.cancel();
      this.toast.success(result.message);
    } catch (error) {
      this.toast.error(toApiError(error).message);
    }
  }

  protected async resumeSubscription(): Promise<void> {
    try {
      const result = await this.subscription.resume();
      this.toast.success(result.message);
    } catch (error) {
      this.toast.error(toApiError(error).message);
    }
  }

  protected async download(invoice: Invoice): Promise<void> {
    try {
      await this.subscription.downloadInvoice(invoice);
    } catch (error) {
      this.toast.error('No se pudo descargar la factura: ' + toApiError(error).message);
    }
  }

  protected reloadInvoices(): void {
    void this.subscription.loadInvoices();
  }

  private clearQueryParams(): void {
    this.location.replaceState('/subscription');
  }
}

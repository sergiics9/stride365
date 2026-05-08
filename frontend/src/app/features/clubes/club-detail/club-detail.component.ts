import { ChangeDetectionStrategy, Component, computed, inject, input, signal } from '@angular/core';
import { Router, RouterLink, RouterLinkActive, RouterOutlet } from '@angular/router';

import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/auth/auth.service';
import { ToastService } from '../../../core/toast/toast.service';
import { Club } from '../../../shared/models';
import { toApiError } from '../../../shared/utils/api-error.util';
import { MembershipsService } from '../../memberships/memberships.service';

@Component({
  selector: 'app-club-detail',
  imports: [RouterLink, RouterLinkActive, RouterOutlet],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './club-detail.component.html',
  styleUrl: './club-detail.component.scss',
})
export class ClubDetailComponent {
  protected readonly auth = inject(AuthService);
  private readonly memberships = inject(MembershipsService);
  private readonly toast = inject(ToastService);
  private readonly router = inject(Router);

  protected readonly pricing = environment.pricing;
  protected readonly subscribing = signal(false);

  readonly club = input<Club | null>(null);

  protected readonly isAdmin = computed(() => {
    const c = this.club();
    return c ? this.auth.isAdminOf(c.id) : false;
  });

  protected readonly isSocio = computed(() => {
    const c = this.club();
    return c ? this.auth.isSocioOf(c.id) : false;
  });

  protected readonly isMember = computed(() => this.isAdmin() || this.isSocio());

  protected readonly canSubscribeAsSocio = computed(() => {
    if (!this.club()) return false;
    if (this.auth.isSuperAdmin()) return false;
    if (this.isAdmin()) return false;
    if (this.isSocio()) return false;
    return true;
  });

  protected async becomeSocio(): Promise<void> {
    const c = this.club();
    if (!c) return;
    this.subscribing.set(true);
    try {
      const baseUrl = window.location.origin + '/memberships';
      const checkout = await this.memberships.checkout({
        kind: 'socio',
        club_id: c.id,
        success_url: `${baseUrl}?status=success&kind=socio&club_id=${c.id}`,
        cancel_url: `${baseUrl}?status=cancel&kind=socio&club_id=${c.id}`,
      });
      if (checkout?.url) {
        window.location.href = checkout.url;
      }
    } catch (err) {
      this.toast.error(toApiError(err).message);
    } finally {
      this.subscribing.set(false);
    }
  }
}

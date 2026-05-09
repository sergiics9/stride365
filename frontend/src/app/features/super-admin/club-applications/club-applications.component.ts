import { DatePipe } from '@angular/common';
import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';

import { ToastService } from '../../../core/toast/toast.service';
import { ClubApplicationStatus } from '../../../shared/models';
import { toApiError } from '../../../shared/utils/api-error.util';
import { ClubApplicationsService } from '../club-applications.service';

@Component({
  selector: 'app-club-applications',
  imports: [DatePipe],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './club-applications.component.html',
  styleUrl: './club-applications.component.scss',
})
export class ClubApplicationsComponent {
  protected readonly service = inject(ClubApplicationsService);
  private readonly toast = inject(ToastService);

  protected readonly statusFilter = signal<ClubApplicationStatus | ''>('pending');
  protected readonly list = this.service.list;
  protected readonly loading = this.service.loading;
  protected readonly error = this.service.error;
  protected readonly action = this.service.action;
  protected readonly actionTarget = this.service.actionTarget;

  protected readonly applications = computed(() => this.list()?.data ?? []);

  constructor() {
    void this.service.load(this.statusFilter());
  }

  protected setStatus(s: ClubApplicationStatus | ''): void {
    this.statusFilter.set(s);
    void this.service.load(s);
  }

  protected async approve(clubId: number): Promise<void> {
    if (!confirm('¿Aprobar este club?')) return;
    try {
      await this.service.approve(clubId);
      this.toast.success('Club aprobado.');
      await this.service.load(this.statusFilter());
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected async reject(clubId: number): Promise<void> {
    const reason = prompt('Motivo del rechazo:');
    if (!reason) return;
    try {
      await this.service.reject(clubId, reason);
      this.toast.success('Club rechazado.');
      await this.service.load(this.statusFilter());
    } catch (err) {
      this.toast.error(toApiError(err).message);
    }
  }

  protected isActing(action: 'approve' | 'reject', clubId: number): boolean {
    return this.action() === action && this.actionTarget() === clubId;
  }
}

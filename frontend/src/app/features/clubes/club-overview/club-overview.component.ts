import { ChangeDetectionStrategy, Component, inject, input } from '@angular/core';

import { Club } from '../../../shared/models';

@Component({
  selector: 'app-club-overview',
  imports: [],
  changeDetection: ChangeDetectionStrategy.OnPush,
  template: `
    @if (club(); as c) {
      <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
          <h2 class="h5 mb-3">Información del club</h2>
          <dl class="row mb-0">
            <dt class="col-sm-3 text-muted">Dirección</dt>
            <dd class="col-sm-9">{{ c.direccion ?? '—' }}</dd>

            <dt class="col-sm-3 text-muted">Teléfono</dt>
            <dd class="col-sm-9">{{ c.telefono ?? '—' }}</dd>

            <dt class="col-sm-3 text-muted">Email</dt>
            <dd class="col-sm-9">{{ c.email ?? '—' }}</dd>

            <dt class="col-sm-3 text-muted">Estado</dt>
            <dd class="col-sm-9">
              @if (c.active && c.application_status === 'approved') {
                <span class="badge bg-success">Activo</span>
              } @else if (c.application_status === 'pending') {
                <span class="badge bg-secondary">Pendiente de aprobación</span>
              } @else if (c.application_status === 'rejected') {
                <span class="badge bg-danger">Rechazado</span>
              } @else {
                <span class="badge bg-warning text-dark">Inactivo</span>
              }
            </dd>
          </dl>
        </div>
      </div>
    }
  `,
})
export class ClubOverviewComponent {
  readonly club = input<Club | null>(null);
}

import { Routes } from '@angular/router';

import { clubMembershipGuard } from '../../core/guards/role.guard';
import { clubDetailResolver } from './club-detail.resolver';

export const CLUBES_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./clubes-list/clubes-list.component').then((m) => m.ClubesListComponent),
  },
  {
    path: 'nuevo',
    loadComponent: () =>
      import('./club-new/club-new.component').then((m) => m.ClubNewComponent),
  },
  {
    path: ':clubId',
    resolve: { club: clubDetailResolver },
    runGuardsAndResolvers: 'pathParamsOrQueryParamsChange',
    loadComponent: () =>
      import('./club-detail/club-detail.component').then((m) => m.ClubDetailComponent),
    children: [
      {
        path: '',
        loadComponent: () =>
          import('./club-overview/club-overview.component').then((m) => m.ClubOverviewComponent),
      },
      {
        path: 'socios',
        canMatch: [clubMembershipGuard('admin')],
        loadChildren: () =>
          import('../socios/socios.routes').then((m) => m.SOCIOS_ROUTES),
      },
      {
        path: 'actividades',
        canMatch: [clubMembershipGuard('admin', 'socio', 'guide')],
        loadChildren: () =>
          import('../actividades/actividades.routes').then((m) => m.ACTIVIDADES_ROUTES),
      },
      {
        path: 'comunicados',
        canMatch: [clubMembershipGuard('admin', 'socio', 'guide')],
        loadChildren: () =>
          import('../comunicados/comunicados.routes').then((m) => m.COMUNICADOS_ROUTES),
      },
    ],
  },
];

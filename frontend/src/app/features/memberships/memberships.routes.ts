import { Routes } from '@angular/router';

export const MEMBERSHIPS_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./memberships-overview/memberships-overview.component').then(
        (m) => m.MembershipsOverviewComponent,
      ),
  },
];

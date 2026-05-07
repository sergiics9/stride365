import { Routes } from '@angular/router';

import { membershipsResolver } from './memberships.resolver';

export const MEMBERSHIPS_ROUTES: Routes = [
  {
    path: '',
    resolve: { membershipsList: membershipsResolver },
    loadComponent: () =>
      import('./memberships-overview/memberships-overview.component').then(
        (m) => m.MembershipsOverviewComponent,
      ),
  },
];

import { Routes } from '@angular/router';

import { subscriptionStatusResolver } from './subscription-status.resolver';

export const SUBSCRIPTION_ROUTES: Routes = [
  {
    path: '',
    resolve: { subscriptionStatus: subscriptionStatusResolver },
    loadComponent: () =>
      import('./subscription-overview/subscription-overview.component').then(
        (m) => m.SubscriptionOverviewComponent,
      ),
  },
];

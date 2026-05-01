import { Routes } from '@angular/router';

export const SUBSCRIPTION_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./subscription-placeholder.component').then(
        (m) => m.SubscriptionPlaceholderComponent,
      ),
  },
];

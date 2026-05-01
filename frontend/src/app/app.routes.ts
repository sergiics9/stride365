import { Routes } from '@angular/router';

import { authGuard, guestGuard } from './core/guards/auth.guard';
import { subscriptionGuard } from './core/guards/subscription.guard';

export const routes: Routes = [
  {
    path: 'auth',
    canMatch: [guestGuard],
    loadComponent: () =>
      import('./layouts/public-layout/public-layout.component').then(
        (m) => m.PublicLayoutComponent,
      ),
    loadChildren: () => import('./features/auth/auth.routes').then((m) => m.AUTH_ROUTES),
  },
  {
    path: '',
    canMatch: [authGuard],
    loadComponent: () =>
      import('./layouts/app-layout/app-layout.component').then((m) => m.AppLayoutComponent),
    children: [
      { path: '', pathMatch: 'full', redirectTo: 'feed' },
      {
        path: 'feed',
        loadChildren: () => import('./features/feed/feed.routes').then((m) => m.FEED_ROUTES),
      },
      {
        path: 'subscription',
        loadChildren: () =>
          import('./features/subscription/subscription.routes').then(
            (m) => m.SUBSCRIPTION_ROUTES,
          ),
      },
      {
        path: 'clubes',
        canMatch: [subscriptionGuard],
        loadChildren: () =>
          import('./features/clubes/clubes.routes').then((m) => m.CLUBES_ROUTES),
      },
      {
        path: 'forbidden',
        loadComponent: () =>
          import('./features/errors/forbidden.component').then((m) => m.ForbiddenComponent),
      },
    ],
  },
  {
    path: '**',
    loadComponent: () =>
      import('./features/errors/not-found.component').then((m) => m.NotFoundComponent),
  },
];

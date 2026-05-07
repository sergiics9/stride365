import { Routes } from '@angular/router';

import { authGuard, guestGuard } from './core/guards/auth.guard';
import { roleGuard } from './core/guards/role.guard';
import { clubAccessGuard } from './core/guards/subscription.guard';

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
        path: 'memberships',
        loadChildren: () =>
          import('./features/memberships/memberships.routes').then(
            (m) => m.MEMBERSHIPS_ROUTES,
          ),
      },
      {
        path: 'subscription',
        redirectTo: 'memberships',
      },
      {
        path: 'perfil',
        loadChildren: () =>
          import('./features/profile/profile.routes').then((m) => m.PROFILE_ROUTES),
      },
      {
        path: 'clubes',
        canMatch: [clubAccessGuard],
        loadChildren: () =>
          import('./features/clubes/clubes.routes').then((m) => m.CLUBES_ROUTES),
      },
      {
        path: 'super-admin',
        canMatch: [roleGuard('super_admin')],
        loadChildren: () =>
          import('./features/super-admin/super-admin.routes').then(
            (m) => m.SUPER_ADMIN_ROUTES,
          ),
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

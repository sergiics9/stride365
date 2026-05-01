import { Routes } from '@angular/router';

export const AUTH_ROUTES: Routes = [
  {
    path: 'login',
    loadComponent: () =>
      import('./auth-placeholder.component').then((m) => m.AuthPlaceholderComponent),
  },
  {
    path: 'register',
    loadComponent: () =>
      import('./auth-placeholder.component').then((m) => m.AuthPlaceholderComponent),
  },
  { path: '', pathMatch: 'full', redirectTo: 'login' },
];

import { Routes } from '@angular/router';

export const CLUBES_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./clubes-placeholder.component').then((m) => m.ClubesPlaceholderComponent),
  },
];

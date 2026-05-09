import { Routes } from '@angular/router';

export const SOCIOS_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./socios-list/socios-list.component').then((m) => m.SociosListComponent),
  },
];

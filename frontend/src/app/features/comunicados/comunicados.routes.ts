import { Routes } from '@angular/router';

export const COMUNICADOS_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./comunicados-list/comunicados-list.component').then(
        (m) => m.ComunicadosListComponent,
      ),
  },
];

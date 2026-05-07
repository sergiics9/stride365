import { Routes } from '@angular/router';

export const ACTIVIDADES_ROUTES: Routes = [
  {
    path: '',
    loadComponent: () =>
      import('./actividades-list/actividades-list.component').then(
        (m) => m.ActividadesListComponent,
      ),
  },
  {
    path: 'nueva',
    loadComponent: () =>
      import('./actividad-form/actividad-form.component').then(
        (m) => m.ActividadFormComponent,
      ),
  },
  {
    path: ':id',
    loadComponent: () =>
      import('./actividad-detail/actividad-detail.component').then(
        (m) => m.ActividadDetailComponent,
      ),
  },
  {
    path: ':id/editar',
    loadComponent: () =>
      import('./actividad-form/actividad-form.component').then(
        (m) => m.ActividadFormComponent,
      ),
  },
];

import { Routes } from '@angular/router';

export const SUPER_ADMIN_ROUTES: Routes = [
  {
    path: '',
    pathMatch: 'full',
    redirectTo: 'clubes/solicitudes',
  },
  {
    path: 'clubes/solicitudes',
    loadComponent: () =>
      import('./club-applications/club-applications.component').then(
        (m) => m.ClubApplicationsComponent,
      ),
  },
];

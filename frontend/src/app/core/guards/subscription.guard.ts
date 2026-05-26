import { inject } from '@angular/core';
import { CanMatchFn, Router } from '@angular/router';

import { AuthService } from '../auth/auth.service';

// Solo exige sesión. Explorar clubes no requiere membresía previa;
// las rutas internas (socios, actividades…) usan otros guards.
export const clubAccessGuard: CanMatchFn = async () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.isAuthenticated()) {
    return router.createUrlTree(['/auth/login']);
  }

  
  
  if (!auth.user()) {
    await auth.me();
  }

  return true;
};

export const subscriptionGuard = clubAccessGuard;

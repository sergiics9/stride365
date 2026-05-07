import { inject } from '@angular/core';
import { CanMatchFn, Router } from '@angular/router';

import { AuthService } from '../auth/auth.service';

/**
 * Módulo Clubes: requiere sesión. Explorar clubes y solicitar uno propio
 * no exigen membresía previa; actividades/socios/etc. usan otros guards.
 */
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

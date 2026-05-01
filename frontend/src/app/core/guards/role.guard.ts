import { inject } from '@angular/core';
import { CanMatchFn, Router } from '@angular/router';

import { RoleName } from '../../shared/models';
import { AuthService } from '../auth/auth.service';

export function roleGuard(...allowedRoles: RoleName[]): CanMatchFn {
  return async () => {
    const auth = inject(AuthService);
    const router = inject(Router);

    if (!auth.isAuthenticated()) {
      return router.createUrlTree(['/auth/login']);
    }

    if (!auth.user()) {
      await auth.me();
    }

    return auth.hasAnyRole(allowedRoles) ? true : router.createUrlTree(['/forbidden']);
  };
}

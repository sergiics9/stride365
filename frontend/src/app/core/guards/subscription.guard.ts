import { inject } from '@angular/core';
import { CanMatchFn, Router } from '@angular/router';

import { AuthService } from '../auth/auth.service';

export const subscriptionGuard: CanMatchFn = async () => {
  const auth = inject(AuthService);
  const router = inject(Router);

  if (!auth.isAuthenticated()) {
    return router.createUrlTree(['/auth/login']);
  }

  if (!auth.user()) {
    await auth.me();
  }

  if (auth.isSuperAdmin() || auth.subscribed()) {
    return true;
  }

  return router.createUrlTree(['/subscription'], {
    queryParams: { reason: 'required' },
  });
};

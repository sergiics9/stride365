import { inject, provideAppInitializer } from '@angular/core';

import { AuthService } from './auth.service';
import { InactivityService } from './inactivity.service';

export const provideAuthInitializer = () =>
  provideAppInitializer(async () => {
    const auth = inject(AuthService);
    inject(InactivityService);

    if (auth.isAuthenticated()) {
      await auth.me();
    }
  });

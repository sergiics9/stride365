import { HttpErrorResponse, HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { Router } from '@angular/router';
import { catchError, throwError } from 'rxjs';

import { AuthService } from '../auth/auth.service';

export const errorInterceptor: HttpInterceptorFn = (req, next) => {
  const auth = inject(AuthService);
  const router = inject(Router);

  return next(req).pipe(
    catchError((error: HttpErrorResponse) => {
      if (error.status === 401) {
        auth.clearSession();
        if (!req.url.endsWith('/auth/login')) {
          void router.navigate(['/auth/login'], {
            queryParams: { reason: 'session-expired' },
          });
        }
      }

      if (error.status === 403) {
        void router.navigate(['/forbidden']);
      }

      return throwError(() => error);
    }),
  );
};

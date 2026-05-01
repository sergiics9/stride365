import { DOCUMENT } from '@angular/common';
import { DestroyRef, Injectable, effect, inject } from '@angular/core';
import { Router } from '@angular/router';

import { environment } from '../../../environments/environment';
import { AuthService } from './auth.service';

const ACTIVITY_EVENTS: ReadonlyArray<keyof DocumentEventMap> = [
  'mousemove',
  'mousedown',
  'keydown',
  'touchstart',
  'scroll',
  'visibilitychange',
];

@Injectable({ providedIn: 'root' })
export class InactivityService {
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly document = inject(DOCUMENT);
  private readonly destroyRef = inject(DestroyRef);

  private timeoutId: ReturnType<typeof setTimeout> | null = null;
  private listenersAttached = false;
  private readonly resetHandler = () => this.scheduleLogout();

  constructor() {
    effect(() => {
      if (this.auth.isAuthenticated()) {
        this.start();
      } else {
        this.stop();
      }
    });

    this.destroyRef.onDestroy(() => this.stop());
  }

  private start(): void {
    if (this.listenersAttached) {
      this.scheduleLogout();
      return;
    }
    for (const eventName of ACTIVITY_EVENTS) {
      this.document.addEventListener(eventName, this.resetHandler, { passive: true });
    }
    this.listenersAttached = true;
    this.scheduleLogout();
  }

  private stop(): void {
    if (this.listenersAttached) {
      for (const eventName of ACTIVITY_EVENTS) {
        this.document.removeEventListener(eventName, this.resetHandler);
      }
      this.listenersAttached = false;
    }
    if (this.timeoutId !== null) {
      clearTimeout(this.timeoutId);
      this.timeoutId = null;
    }
  }

  private scheduleLogout(): void {
    if (this.timeoutId !== null) {
      clearTimeout(this.timeoutId);
    }
    this.timeoutId = setTimeout(() => {
      this.auth.clearSession();
      void this.router.navigate(['/auth/login'], {
        queryParams: { reason: 'inactivity' },
      });
    }, environment.inactivityTimeoutMs);
  }
}

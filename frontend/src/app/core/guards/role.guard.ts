import { inject } from '@angular/core';
import {
  CanMatchFn,
  Navigation,
  PRIMARY_OUTLET,
  Router,
  UrlSegment,
  UrlTree,
} from '@angular/router';

import { RoleName } from '../../shared/models';
import { AuthService } from '../auth/auth.service';

// Busca el id del club en la URL completa, p. ej. /clubes/3/actividades → 3.
function clubIdFromUrlTree(tree: UrlTree | null | undefined): number | null {
  const primary = tree?.root.children[PRIMARY_OUTLET];
  if (!primary) {
    return null;
  }
  const paths = primary.segments.map((s) => s.path);
  const i = paths.indexOf('clubes');
  if (i < 0 || i + 1 >= paths.length) {
    return null;
  }
  const next = paths[i + 1];
  return /^\d+$/.test(next) ? Number(next) : null;
}

function clubIdFromSegmentList(segments: readonly UrlSegment[]): number | null {
  for (let i = 0; i < segments.length - 1; i++) {
    if (segments[i].path === 'clubes' || segments[i].path === 'club') {
      const next = segments[i + 1]?.path;
      if (next && /^\d+$/.test(next)) {
        return Number(next);
      }
    }
  }
  return null;
}


// En rutas lazy (`loadChildren`), Angular a veces solo pasa el trozo que se
// está emparejando (p. ej. "actividades"), no la URL entera. Por eso miramos
// la navegación en curso y, si hace falta, la URL actual del router.
function resolveClubIdForGuard(router: Router, segments: readonly UrlSegment[]): number | null {
  const nav: Navigation | null = router.currentNavigation() ?? router.getCurrentNavigation();

  const fromNav =
    clubIdFromUrlTree(nav?.extractedUrl) ??
    clubIdFromUrlTree(nav?.finalUrl) ??
    clubIdFromUrlTree(nav?.initialUrl);

  if (fromNav !== null) {
    return fromNav;
  }

  const fromLocal = clubIdFromSegmentList(segments);
  if (fromLocal !== null) {
    return fromLocal;
  }

  return clubIdFromUrlTree(router.parseUrl(router.url));
}


// Comprueba roles globales de Spatie (super_admin, usuario).
// Para permisos dentro de un club concreto, usar clubMembershipGuard.
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

    const has = allowedRoles.some((r) => auth.hasRole(r));
    return has ? true : router.createUrlTree(['/forbidden']);
  };
}


// Valida si el usuario puede entrar a una ruta de un club concreto
// (admin, socio o guía). El super_admin pasa siempre.
export function clubMembershipGuard(
  ...allowed: Array<'admin' | 'socio' | 'guide'>
): CanMatchFn {
  return async (_route, segments) => {
    const auth = inject(AuthService);
    const router = inject(Router);

    if (!auth.isAuthenticated()) {
      return router.createUrlTree(['/auth/login']);
    }
    if (!auth.user()) {
      await auth.me();
    }
    if (auth.isSuperAdmin()) {
      return true;
    }

    const clubId = resolveClubIdForGuard(router, segments);

    if (clubId === null) {
      return router.createUrlTree(['/forbidden']);
    }

    const ok = allowed.some((role) =>
      role === 'admin'
        ? auth.isAdminOf(clubId!)
        : role === 'socio'
          ? auth.isSocioOf(clubId!)
          : auth.isGuideOf(clubId!),
    );

    return ok ? true : router.createUrlTree(['/forbidden']);
  };
}

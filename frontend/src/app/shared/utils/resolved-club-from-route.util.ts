import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { merge, Observable, of } from 'rxjs';
import { filter, map } from 'rxjs/operators';

import { Club } from '../models';

/**
 * El `resolve` del club vive en el segmento `:clubId`. Las rutas lazy hijas
 * (`actividades`, `socios`, etc.) no reciben ese `data` en el padre inmediato.
 */
export function findResolvedClub(route: ActivatedRoute): Club | null {
  let r: ActivatedRoute | null = route;
  while (r) {
    const club = r.snapshot.data['club'] as Club | undefined;
    if (club) {
      return club;
    }
    r = r.parent;
  }
  return null;
}

/** Emite de nuevo el club resuelto tras cada navegación terminada. */
export function resolvedClub$(route: ActivatedRoute, router: Router): Observable<Club | null> {
  return merge(
    of(null),
    router.events.pipe(filter((e): e is NavigationEnd => e instanceof NavigationEnd)),
  ).pipe(map(() => findResolvedClub(route)));
}

import { ActivatedRoute, NavigationEnd, Router } from '@angular/router';
import { merge, Observable, of } from 'rxjs';
import { filter, map } from 'rxjs/operators';

import { Club } from '../models';

// El club activo lo resuelve un Route Resolver y queda en `route.data['club']`.
// Subimos por el árbol de rutas por si estamos en un hijo (p. ej. actividades).
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


// Vuelve a leer el club resuelto cada vez que termina una navegación,
// para que los componentes reaccionen al cambiar de club sin recargar.
export function resolvedClub$(route: ActivatedRoute, router: Router): Observable<Club | null> {
  return merge(
    of(null),
    router.events.pipe(filter((e): e is NavigationEnd => e instanceof NavigationEnd)),
  ).pipe(map(() => findResolvedClub(route)));
}

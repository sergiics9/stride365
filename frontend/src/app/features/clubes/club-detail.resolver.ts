import { inject } from '@angular/core';
import { ResolveFn, Router } from '@angular/router';

import { Club } from '../../shared/models';
import { ClubesService } from './clubes.service';

export const clubDetailResolver: ResolveFn<Club | null> = async (route) => {
  const service = inject(ClubesService);
  const router = inject(Router);

  const idParam = route.paramMap.get('clubId');
  const id = idParam ? Number(idParam) : NaN;
  if (!Number.isFinite(id)) {
    router.navigate(['/clubes']);
    return null;
  }

  const club = await service.getById(id);
  if (!club) {
    router.navigate(['/clubes']);
    return null;
  }
  return club;
};

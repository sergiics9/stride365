import { inject } from '@angular/core';
import { ActivatedRouteSnapshot, ResolveFn, Router } from '@angular/router';

import { PublicacionFeed } from '../../shared/models';
import { FeedService } from './feed.service';

export const feedDetailResolver: ResolveFn<PublicacionFeed | null> = async (
  route: ActivatedRouteSnapshot,
) => {
  const feedService = inject(FeedService);
  const router = inject(Router);
  const id = route.paramMap.get('id');

  if (!id) {
    void router.navigate(['/feed']);
    return null;
  }

  try {
    return await feedService.getById(id);
  } catch {
    void router.navigate(['/feed']);
    return null;
  }
};

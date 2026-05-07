import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';

import { Membership } from '../../shared/models';
import { MembershipsService } from './memberships.service';

export const membershipsResolver: ResolveFn<Membership[]> = async () => {
  const service = inject(MembershipsService);
  return service.loadMemberships(true);
};

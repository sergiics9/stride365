import { inject } from '@angular/core';
import { ResolveFn } from '@angular/router';

import { SubscriptionStatus } from '../../shared/models';
import { SubscriptionService } from './subscription.service';

export const subscriptionStatusResolver: ResolveFn<SubscriptionStatus | null> = async () => {
  const service = inject(SubscriptionService);
  return service.loadStatus(true);
};

import type { StripePlan } from '../app/shared/models/stripe-plan.model';

export const environment = {
  production: true,
  apiUrl: '/api',
  inactivityTimeoutMs: 30 * 60 * 1000,
  stripe: {
    plans: [
      {
        id: 'annual',
        name: 'Plan Clubes Anual',
        priceId: 'price_REPLACE_ME',
        description: 'Acceso completo al módulo Clubes con renovación anual.',
        amountLabel: '39,99 € / año',
      },
    ] as StripePlan[],
  },
};

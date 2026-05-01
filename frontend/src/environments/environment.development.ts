import type { StripePlan } from '../app/shared/models/stripe-plan.model';

export const environment = {
  production: false,
  apiUrl: 'http://localhost:8000/api',
  inactivityTimeoutMs: 30 * 60 * 1000,
  stripe: {
    plans: [
      {
        id: 'annual',
        name: 'Plan Clubes Anual',
        priceId: 'price_1TSKQmCnqKLwPpPSRTVKwcUT',
        description: 'Acceso completo al módulo Clubes con renovación anual.',
        amountLabel: '39,99 € / año',
      },
    ] as StripePlan[],
  },
};

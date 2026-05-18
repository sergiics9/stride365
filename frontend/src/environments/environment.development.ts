export const environment = {
  production: false,
  apiUrl: 'https://stride365.com/api',
  inactivityTimeoutMs: 30 * 60 * 1000,
  pricing: {
    club: {
      label: 'Cuota anual de club',
      amountLabel: '129,99 $ / año',
      description: 'Permite registrar y mantener un club en la plataforma.',
    },
    socio: {
      label: 'Cuota anual de socio',
      amountLabel: '39,99 $ / año',
      description: 'Acceso a actividades, inscripciones y comunicados del club.',
    },
  },
};

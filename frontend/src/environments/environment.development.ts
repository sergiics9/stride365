export const environment = {
  production: false,
  apiUrl: 'http://127.0.0.1:8000/api',
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

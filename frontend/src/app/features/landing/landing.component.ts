import { ChangeDetectionStrategy, Component, inject } from '@angular/core';
import { RouterLink } from '@angular/router';

import { environment } from '../../../environments/environment';
import { AuthService } from '../../core/auth/auth.service';
import { ThemeService } from '../../core/theme/theme.service';

interface FeatureItem {
  title: string;
  description: string;
  icon: string;
}

interface StepItem {
  title: string;
  description: string;
}

@Component({
  selector: 'app-landing',
  standalone: true,
  imports: [RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './landing.component.html',
  styleUrl: './landing.component.scss',
})
export class LandingComponent {
  protected readonly auth = inject(AuthService);
  protected readonly theme = inject(ThemeService);

  protected readonly pricing = environment.pricing;

  protected readonly features: FeatureItem[] = [
    {
      title: 'Gestión de clubes',
      description:
        'Crea y administra tu club deportivo: socios, comunicados, actividades y datos de contacto en un único panel.',
      icon: 'shield',
    },
    {
      title: 'Socios y guías',
      description:
        'Mantén el control de las altas, bajas, cuotas y roles de cada miembro. Asigna guías para liderar actividades.',
      icon: 'people',
    },
    {
      title: 'Actividades',
      description:
        'Planifica salidas, entrenamientos y eventos. Tus socios podrán inscribirse y recibir avisos al instante.',
      icon: 'calendar',
    },
    {
      title: 'Feed social',
      description:
        'Un muro donde clubes y atletas comparten sus avances, fotos y comunicados. Mantén viva la comunidad.',
      icon: 'feed',
    },
    {
      title: 'Comunicados',
      description:
        'Envía noticias y avisos importantes a tus socios. Llega siempre a las personas adecuadas.',
      icon: 'megaphone',
    },
    {
      title: 'Pagos seguros con Stripe',
      description:
        'Cuotas anuales gestionadas con Stripe. Facturas automáticas y renovación sin complicaciones.',
      icon: 'card',
    },
  ];

  protected readonly steps: StepItem[] = [
    {
      title: 'Crea tu cuenta',
      description: 'Regístrate en menos de un minuto con tu correo electrónico.',
    },
    {
      title: 'Únete o crea un club',
      description:
        'Solicita el alta como socio en un club existente o crea el tuyo propio si gestionas uno.',
    },
    {
      title: 'Vive tu deporte',
      description:
        'Apúntate a actividades, comparte en el feed y mantente al día con los comunicados.',
    },
  ];

  protected scrollTo(id: string): void {
    const el = document.getElementById(id);
    if (el) {
      el.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  }
}

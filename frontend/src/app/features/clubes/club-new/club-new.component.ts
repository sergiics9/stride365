import {
  ChangeDetectionStrategy,
  Component,
  OnDestroy,
  computed,
  inject,
  signal,
} from '@angular/core';
import {
  FormBuilder,
  FormGroup,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { RouterLink } from '@angular/router';

import { environment } from '../../../../environments/environment';
import { AuthService } from '../../../core/auth/auth.service';
import { ToastService } from '../../../core/toast/toast.service';
import { toApiError } from '../../../shared/utils/api-error.util';
import { ClubesService } from '../clubes.service';
import { MembershipsService } from '../../memberships/memberships.service';

const MAX_LOGO_BYTES = 2 * 1024 * 1024;
const ACCEPTED_LOGO_MIME = new Set([
  'image/jpeg',
  'image/jpg',
  'image/png',
  'image/webp',
  'image/gif',
]);

@Component({
  selector: 'app-club-new',
  imports: [ReactiveFormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './club-new.component.html',
  styleUrl: './club-new.component.scss',
})
export class ClubNewComponent implements OnDestroy {
  private readonly fb = inject(FormBuilder);
  private readonly clubes = inject(ClubesService);
  private readonly memberships = inject(MembershipsService);
  private readonly toast = inject(ToastService);
  protected readonly auth = inject(AuthService);

  protected readonly pricing = environment.pricing;

  protected readonly form: FormGroup = this.fb.group({
    nombre: ['', [Validators.required, Validators.maxLength(150)]],
    descripcion: ['', [Validators.maxLength(2000)]],
    direccion: ['', [Validators.maxLength(255)]],
    telefono: ['', [Validators.maxLength(50)]],
    email: ['', [Validators.email, Validators.maxLength(150)]],
  });

  protected readonly submitting = signal(false);
  protected readonly serverError = signal<string | null>(null);
  protected readonly logoFile = signal<File | null>(null);
  protected readonly logoPreviewUrl = signal<string | null>(null);
  protected readonly logoFieldError = signal<string | null>(null);

  private logoObjectUrl: string | null = null;

  protected readonly hasAdminMembership = computed(() => !!this.auth.adminMembership());

  ngOnDestroy(): void {
    this.revokeLogoPreview();
  }

  private revokeLogoPreview(): void {
    if (this.logoObjectUrl) {
      URL.revokeObjectURL(this.logoObjectUrl);
      this.logoObjectUrl = null;
    }
  }

  protected onLogoSelected(event: Event): void {
    this.logoFieldError.set(null);
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    this.revokeLogoPreview();
    this.logoPreviewUrl.set(null);
    this.logoFile.set(null);

    if (!file) {
      return;
    }

    if (!ACCEPTED_LOGO_MIME.has(file.type)) {
      this.logoFieldError.set('Formato no válido. Usa JPEG, PNG, WebP o GIF.');
      input.value = '';
      return;
    }

    if (file.size > MAX_LOGO_BYTES) {
      this.logoFieldError.set('La imagen no puede superar 2 MB.');
      input.value = '';
      return;
    }

    this.logoFile.set(file);
    this.logoObjectUrl = URL.createObjectURL(file);
    this.logoPreviewUrl.set(this.logoObjectUrl);
  }

  protected clearLogo(logoInput: HTMLInputElement): void {
    logoInput.value = '';
    this.logoFieldError.set(null);
    this.revokeLogoPreview();
    this.logoPreviewUrl.set(null);
    this.logoFile.set(null);
  }

  protected fieldError(name: string): string | null {
    const ctrl = this.form.get(name);
    if (!ctrl?.touched && !ctrl?.dirty) return null;
    if (!ctrl?.errors) return null;
    if (ctrl.errors['required']) return 'Campo obligatorio.';
    if (ctrl.errors['email']) return 'Email no válido.';
    if (ctrl.errors['maxlength']) return 'Demasiado largo.';
    return null;
  }

  protected async submit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }
    if (this.hasAdminMembership()) {
      this.toast.warning('Ya administras un club. Solo puedes tener uno a la vez.');
      return;
    }

    this.submitting.set(true);
    this.serverError.set(null);

    try {
      const v = this.form.getRawValue();
      const club = await this.clubes.createApplication({
        nombre: v.nombre,
        descripcion: v.descripcion || null,
        logo: this.logoFile() ?? undefined,
        direccion: v.direccion || null,
        telefono: v.telefono || null,
        email: v.email || null,
      });

      this.toast.info('Solicitud creada. Procediendo al pago de la cuota anual…');

      const baseUrl = window.location.origin + '/memberships';
      const checkout = await this.memberships.checkout({
        kind: 'club',
        club_id: club.id,
        success_url: `${baseUrl}?status=success&kind=club&club_id=${club.id}`,
        cancel_url: `${baseUrl}?status=cancel&kind=club&club_id=${club.id}`,
      });

      if (checkout?.url) {
        window.location.href = checkout.url;
      } else {
        this.toast.error('No se pudo iniciar el pago. Vuelve a intentarlo.');
      }
    } catch (err) {
      const apiErr = toApiError(err);
      const firstField = apiErr.errors ? Object.values(apiErr.errors).flat()[0] : null;
      this.serverError.set(firstField ?? apiErr.message);
      this.toast.error(this.serverError()!);
    } finally {
      this.submitting.set(false);
    }
  }
}

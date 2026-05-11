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
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../core/auth/auth.service';
import { ApiError } from '../../../shared/models';
import { firstFieldError, toApiError } from '../../../shared/utils/api-error.util';
import { matchValidator } from '../../../shared/validators/match.validator';

const MAX_FOTO_BYTES = 2 * 1024 * 1024;
const ACCEPTED_FOTO_MIME = new Set([
  'image/jpeg',
  'image/jpg',
  'image/png',
  'image/webp',
  'image/gif',
]);

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss',
})
export class RegisterComponent implements OnDestroy {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly form = this.fb.nonNullable.group(
    {
      nombre: ['', [Validators.required, Validators.maxLength(255)]],
      apellido: ['', [Validators.required, Validators.maxLength(255)]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
      telefono: ['', [Validators.maxLength(50)]],
      sexo: ['' as '' | 'M' | 'F' | 'O', []],
      fecha_nacimiento: ['', []],
      direccion: ['', [Validators.maxLength(500)]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: matchValidator('password', 'password_confirmation') },
  );

  protected readonly submitting = signal(false);
  protected readonly serverError = signal<ApiError | null>(null);
  protected readonly showPassword = signal(false);
  protected readonly fotoFile = signal<File | null>(null);
  protected readonly fotoPreviewUrl = signal<string | null>(null);
  protected readonly fotoClientError = signal<string | null>(null);

  private fotoObjectUrl: string | null = null;

  protected readonly nombreError = computed(() => firstFieldError(this.serverError(), 'nombre'));
  protected readonly apellidoError = computed(() =>
    firstFieldError(this.serverError(), 'apellido'),
  );
  protected readonly emailError = computed(() => firstFieldError(this.serverError(), 'email'));
  protected readonly telefonoError = computed(() =>
    firstFieldError(this.serverError(), 'telefono'),
  );
  protected readonly sexoError = computed(() => firstFieldError(this.serverError(), 'sexo'));
  protected readonly fechaNacimientoError = computed(() =>
    firstFieldError(this.serverError(), 'fecha_nacimiento'),
  );
  protected readonly direccionError = computed(() =>
    firstFieldError(this.serverError(), 'direccion'),
  );
  protected readonly passwordError = computed(() =>
    firstFieldError(this.serverError(), 'password'),
  );
  protected readonly fotoError = computed(() => firstFieldError(this.serverError(), 'foto'));

  ngOnDestroy(): void {
    this.revokeFotoPreview();
  }

  private revokeFotoPreview(): void {
    if (this.fotoObjectUrl) {
      URL.revokeObjectURL(this.fotoObjectUrl);
      this.fotoObjectUrl = null;
    }
  }

  protected togglePassword(): void {
    this.showPassword.update((v) => !v);
  }

  protected onFotoSelected(event: Event): void {
    this.fotoClientError.set(null);
    this.serverError.set(null);
    const input = event.target as HTMLInputElement;
    const file = input.files?.[0] ?? null;

    this.revokeFotoPreview();
    this.fotoPreviewUrl.set(null);
    this.fotoFile.set(null);

    if (!file) {
      return;
    }

    if (!ACCEPTED_FOTO_MIME.has(file.type)) {
      this.fotoClientError.set('Formato no válido. Usa JPEG, PNG, WebP o GIF.');
      input.value = '';
      return;
    }

    if (file.size > MAX_FOTO_BYTES) {
      this.fotoClientError.set('La imagen no puede superar 2 MB.');
      input.value = '';
      return;
    }

    this.fotoFile.set(file);
    this.fotoObjectUrl = URL.createObjectURL(file);
    this.fotoPreviewUrl.set(this.fotoObjectUrl);
  }

  protected clearFoto(fotoInput: HTMLInputElement): void {
    fotoInput.value = '';
    this.fotoClientError.set(null);
    this.revokeFotoPreview();
    this.fotoPreviewUrl.set(null);
    this.fotoFile.set(null);
  }

  protected async onSubmit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.serverError.set(null);

    const value = this.form.getRawValue();

    try {
      await this.auth.register({
        nombre: value.nombre.trim(),
        apellido: value.apellido.trim(),
        email: value.email.trim(),
        telefono: value.telefono.trim() || null,
        sexo: (value.sexo || null) as 'M' | 'F' | 'O' | null,
        fecha_nacimiento: value.fecha_nacimiento || null,
        direccion: value.direccion.trim() || null,
        password: value.password,
        password_confirmation: value.password_confirmation,
        device_name: navigator.userAgent.slice(0, 100),
        foto: this.fotoFile() ?? undefined,
      });

      void this.router.navigateByUrl('/feed');
    } catch (error) {
      this.serverError.set(toApiError(error));
    } finally {
      this.submitting.set(false);
    }
  }
}

import { ChangeDetectionStrategy, Component, computed, inject, signal } from '@angular/core';
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

@Component({
  selector: 'app-register',
  imports: [ReactiveFormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './register.component.html',
  styleUrl: './register.component.scss',
})
export class RegisterComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly form = this.fb.nonNullable.group(
    {
      nombre: ['', [Validators.required, Validators.maxLength(255)]],
      apellido: ['', [Validators.required, Validators.maxLength(255)]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
      telefono: ['', [Validators.maxLength(50)]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: matchValidator('password', 'password_confirmation') },
  );

  protected readonly submitting = signal(false);
  protected readonly serverError = signal<ApiError | null>(null);
  protected readonly showPassword = signal(false);

  protected readonly nombreError = computed(() => firstFieldError(this.serverError(), 'nombre'));
  protected readonly apellidoError = computed(() =>
    firstFieldError(this.serverError(), 'apellido'),
  );
  protected readonly emailError = computed(() => firstFieldError(this.serverError(), 'email'));
  protected readonly telefonoError = computed(() =>
    firstFieldError(this.serverError(), 'telefono'),
  );
  protected readonly passwordError = computed(() =>
    firstFieldError(this.serverError(), 'password'),
  );

  protected togglePassword(): void {
    this.showPassword.update((v) => !v);
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
        password: value.password,
        password_confirmation: value.password_confirmation,
        device_name: navigator.userAgent.slice(0, 100),
      });

      void this.router.navigateByUrl('/feed');
    } catch (error) {
      this.serverError.set(toApiError(error));
    } finally {
      this.submitting.set(false);
    }
  }
}

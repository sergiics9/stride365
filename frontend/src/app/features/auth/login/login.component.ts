import { ChangeDetectionStrategy, Component, computed, inject, input, signal } from '@angular/core';
import {
  FormBuilder,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../core/auth/auth.service';
import { ApiError } from '../../../shared/models';
import { firstFieldError, toApiError } from '../../../shared/utils/api-error.util';

@Component({
  selector: 'app-login',
  imports: [ReactiveFormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './login.component.html',
  styleUrl: './login.component.scss',
})
export class LoginComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  readonly returnUrl = input<string | null>(null);
  readonly reason = input<string | null>(null);

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email]],
    password: ['', [Validators.required, Validators.minLength(8)]],
  });

  protected readonly submitting = signal(false);
  protected readonly serverError = signal<ApiError | null>(null);
  protected readonly showPassword = signal(false);

  protected readonly emailError = computed(() => firstFieldError(this.serverError(), 'email'));
  protected readonly passwordError = computed(() =>
    firstFieldError(this.serverError(), 'password'),
  );

  protected readonly notice = computed(() => {
    switch (this.reason()) {
      case 'inactivity':
        return 'Tu sesión ha expirado por inactividad. Vuelve a iniciar sesión.';
      case 'session-expired':
        return 'Tu sesión ha caducado. Vuelve a iniciar sesión.';
      default:
        return null;
    }
  });

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

    try {
      await this.auth.login({
        email: this.form.controls.email.value.trim(),
        password: this.form.controls.password.value,
        device_name: navigator.userAgent.slice(0, 100),
      });

      const target = this.returnUrl() ?? '/feed';
      void this.router.navigateByUrl(target);
    } catch (error) {
      this.serverError.set(toApiError(error));
    } finally {
      this.submitting.set(false);
    }
  }
}

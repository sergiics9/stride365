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

@Component({
  selector: 'app-forgot-password',
  imports: [ReactiveFormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './forgot-password.component.html',
  styleUrl: './forgot-password.component.scss',
})
export class ForgotPasswordComponent {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);

  protected readonly form = this.fb.nonNullable.group({
    email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
  });

  protected readonly submitting = signal(false);
  protected readonly serverError = signal<ApiError | null>(null);
  protected readonly successMessage = signal<string | null>(null);

  protected readonly emailError = computed(() => firstFieldError(this.serverError(), 'email'));

  protected async onSubmit(): Promise<void> {
    if (this.form.invalid) {
      this.form.markAllAsTouched();
      return;
    }

    this.submitting.set(true);
    this.serverError.set(null);
    this.successMessage.set(null);

    try {
      const res = await this.auth.requestPasswordReset({
        email: this.form.controls.email.value.trim(),
      });
      this.successMessage.set(res.message);
    } catch (error) {
      this.serverError.set(toApiError(error));
    } finally {
      this.submitting.set(false);
    }
  }

  protected goToLogin(): void {
    void this.router.navigate(['/auth/login']);
  }
}

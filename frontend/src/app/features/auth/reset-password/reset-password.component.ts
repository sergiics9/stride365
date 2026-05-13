import {
  ChangeDetectionStrategy,
  Component,
  computed,
  inject,
  OnInit,
  signal,
} from '@angular/core';
import {
  FormBuilder,
  ReactiveFormsModule,
  Validators,
} from '@angular/forms';
import { ActivatedRoute, Router, RouterLink } from '@angular/router';

import { AuthService } from '../../../core/auth/auth.service';
import { ApiError } from '../../../shared/models';
import { firstFieldError, toApiError } from '../../../shared/utils/api-error.util';
import { matchValidator } from '../../../shared/validators/match.validator';

@Component({
  selector: 'app-reset-password',
  imports: [ReactiveFormsModule, RouterLink],
  changeDetection: ChangeDetectionStrategy.OnPush,
  templateUrl: './reset-password.component.html',
  styleUrl: './reset-password.component.scss',
})
export class ResetPasswordComponent implements OnInit {
  private readonly fb = inject(FormBuilder);
  private readonly auth = inject(AuthService);
  private readonly router = inject(Router);
  private readonly route = inject(ActivatedRoute);

  protected readonly form = this.fb.nonNullable.group(
    {
      token: ['', [Validators.required]],
      email: ['', [Validators.required, Validators.email, Validators.maxLength(255)]],
      password: ['', [Validators.required, Validators.minLength(8)]],
      password_confirmation: ['', [Validators.required]],
    },
    { validators: matchValidator('password', 'password_confirmation') },
  );

  protected readonly submitting = signal(false);
  protected readonly serverError = signal<ApiError | null>(null);
  protected readonly showPassword = signal(false);
  protected readonly queryMissing = signal(false);

  protected readonly emailError = computed(() => firstFieldError(this.serverError(), 'email'));
  protected readonly passwordError = computed(() =>
    firstFieldError(this.serverError(), 'password'),
  );

  ngOnInit(): void {
    const q = this.route.snapshot.queryParamMap;
    const token = q.get('token') ?? '';
    const email = q.get('email') ?? '';
    if (!token || !email) {
      this.queryMissing.set(true);
      return;
    }
    this.form.patchValue({ token, email });
  }

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
      await this.auth.resetPassword({
        token: this.form.controls.token.value,
        email: this.form.controls.email.value.trim(),
        password: this.form.controls.password.value,
        password_confirmation: this.form.controls.password_confirmation.value,
      });
      await this.router.navigate(['/auth/login'], {
        queryParams: { reason: 'password-reset' },
      });
    } catch (error) {
      this.serverError.set(toApiError(error));
    } finally {
      this.submitting.set(false);
    }
  }
}

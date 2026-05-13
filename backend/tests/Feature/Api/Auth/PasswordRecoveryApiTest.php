<?php

namespace Tests\Feature\Api\Auth;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordRecoveryApiTest extends TestCase
{
    use RefreshDatabase;

    private function createActiveUser(string $email = 'recovery@example.com'): User
    {
        return User::create([
            'nombre' => 'Test',
            'apellido' => 'User',
            'email' => $email,
            'password' => Hash::make('Old-password-1'),
            'fecha_alta' => now()->toDateString(),
            'estado' => 'activo',
        ]);
    }

    public function test_forgot_password_validates_email(): void
    {
        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'not-an-email',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }

    public function test_forgot_password_sends_notification_with_spa_url(): void
    {
        Notification::fake();

        $user = $this->createActiveUser();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Si existe una cuenta con ese correo, recibirás un enlace para restablecer la contraseña.',
            ]);

        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use ($user): bool {
            $mail = $notification->toMail($user);

            $this->assertStringContainsString('http://spa.test/auth/reset-password', $mail->actionUrl);
            $this->assertStringContainsString('token=', $mail->actionUrl);
            $this->assertStringContainsString('email=', $mail->actionUrl);

            return true;
        });
    }

    public function test_forgot_password_unknown_email_returns_same_generic_message(): void
    {
        Notification::fake();

        $response = $this->postJson('/api/auth/forgot-password', [
            'email' => 'nobody@example.com',
        ]);

        $response->assertOk()
            ->assertJsonFragment([
                'message' => 'Si existe una cuenta con ese correo, recibirás un enlace para restablecer la contraseña.',
            ]);

        Notification::assertNothingSent();
    }

    public function test_reset_password_updates_password_and_allows_login(): void
    {
        Notification::fake();

        $user = $this->createActiveUser();

        $this->postJson('/api/auth/forgot-password', [
            'email' => $user->email,
        ])->assertOk();

        $plainToken = null;
        Notification::assertSentTo($user, ResetPassword::class, function (ResetPassword $notification) use (&$plainToken): bool {
            $plainToken = $notification->token;

            return true;
        });

        $this->assertNotNull($plainToken);

        $newPassword = 'New-secure-pass-99';

        $this->postJson('/api/auth/reset-password', [
            'token' => $plainToken,
            'email' => $user->email,
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])->assertOk()
            ->assertJsonStructure(['message']);

        $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => $newPassword,
            'device_name' => 'phpunit',
        ])->assertOk()
            ->assertJsonStructure(['token', 'user']);
    }

    public function test_reset_password_rejects_invalid_token(): void
    {
        $user = $this->createActiveUser();

        $this->postJson('/api/auth/reset-password', [
            'token' => 'invalid-token',
            'email' => $user->email,
            'password' => 'New-secure-pass-99',
            'password_confirmation' => 'New-secure-pass-99',
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['email']);
    }
}

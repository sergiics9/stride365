<?php

namespace App\Providers;

use App\Support\BrandLogo;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token): string {
            $base = rtrim((string) config('app.frontend_url'), '/');
            $email = urlencode($notifiable->getEmailForPasswordReset());

            return $base.'/auth/reset-password?token='.urlencode($token).'&email='.$email;
        });

        ResetPassword::toMailUsing(function (object $notifiable, string $token): MailMessage {
            $base = rtrim((string) config('app.frontend_url'), '/');
            $email = urlencode($notifiable->getEmailForPasswordReset());
            $url = $base.'/auth/reset-password?token='.urlencode($token).'&email='.$email;

            return (new MailMessage)
                ->subject('Restablecer contraseña - '.BrandLogo::name())
                ->view('mail.reset-password', [
                    'notifiable' => $notifiable,
                    'url' => $url,
                    'expire' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
                ]);
        });
    }
}

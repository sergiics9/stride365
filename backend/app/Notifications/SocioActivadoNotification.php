<?php

namespace App\Notifications;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SocioActivadoNotification extends Notification
{
    use Queueable;

    public function __construct(public Club $club, public string $role) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $esAdmin = $this->role === 'admin_club';

        return (new MailMessage)
            ->subject($esAdmin
                ? 'Tu suscripción de administrador está activa - '.$this->club->nombre
                : 'Bienvenido/a al club '.$this->club->nombre)
            ->view('mail.socio-activado', [
                'notifiable' => $notifiable,
                'club' => $this->club,
                'esAdmin' => $esAdmin,
            ]);
    }
}

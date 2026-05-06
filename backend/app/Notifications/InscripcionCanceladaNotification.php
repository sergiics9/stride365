<?php

namespace App\Notifications;

use App\Models\Actividad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InscripcionCanceladaNotification extends Notification
{
    use Queueable;

    public function __construct(public Actividad $actividad, public ?string $motivo = null) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Inscripción cancelada: '.$this->actividad->titulo)
            ->view('mail.inscripcion-cancelada', [
                'notifiable' => $notifiable,
                'actividad' => $this->actividad,
                'motivo' => $this->motivo,
            ]);
    }
}

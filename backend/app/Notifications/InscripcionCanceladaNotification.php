<?php

namespace App\Notifications;

use App\Models\Actividad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InscripcionCanceladaNotification extends Notification
{
    use Queueable;

    public function __construct(public Actividad $actividad, public ?string $motivo = null)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Inscripción cancelada: '.$this->actividad->titulo)
            ->greeting('Hola '.($notifiable->nombre ?? ''))
            ->line('Tu inscripción a la actividad **'.$this->actividad->titulo.'** ha sido cancelada.');

        if ($this->motivo) {
            $mail->line('Motivo: '.$this->motivo);
        }

        return $mail->line('Si crees que es un error, contacta con tu club.');
    }
}

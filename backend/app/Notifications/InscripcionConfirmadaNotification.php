<?php

namespace App\Notifications;

use App\Models\Actividad;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class InscripcionConfirmadaNotification extends Notification
{
    use Queueable;

    public function __construct(public Actividad $actividad)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject('Inscripción confirmada: '.$this->actividad->titulo)
            ->greeting('Hola '.($notifiable->nombre ?? ''))
            ->line('Tu inscripción a la actividad **'.$this->actividad->titulo.'** ha sido confirmada.')
            ->line('Fecha: '.optional($this->actividad->fecha_inicio)->format('d/m/Y H:i'));

        if ($this->actividad->lugar) {
            $mail->line('Lugar: '.$this->actividad->lugar);
        }

        return $mail->line('¡Nos vemos allí!');
    }
}

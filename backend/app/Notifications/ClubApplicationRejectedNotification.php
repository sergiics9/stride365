<?php

namespace App\Notifications;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClubApplicationRejectedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Club $club,
        public string $reason,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Actualización sobre tu solicitud de club: '.$this->club->nombre)
            ->view('mail.club-solicitud-rechazada', [
                'notifiable' => $notifiable,
                'club' => $this->club,
                'reason' => $this->reason,
            ]);
    }
}

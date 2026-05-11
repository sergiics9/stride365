<?php

namespace App\Notifications;

use App\Models\Club;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ClubApplicationApprovedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Club $club,
        public string $clubUrl,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Tu solicitud de club ha sido aprobada: '.$this->club->nombre)
            ->view('mail.club-solicitud-aprobada', [
                'notifiable' => $notifiable,
                'club' => $this->club,
                'clubUrl' => $this->clubUrl,
            ]);
    }
}

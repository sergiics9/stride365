<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Laravel\Cashier\Invoice;

class FacturaGeneradaNotification extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice)
    {
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $number = $this->invoice->asStripeInvoice()->number ?? $this->invoice->asStripeInvoice()->id;
        $total = $this->invoice->total();

        $filename = 'factura-'.($number ?? 'suscripcion').'.pdf';

        $pdf = $this->invoice->pdf([
            'vendor' => config('app.name'),
            'product' => 'Suscripción módulo Clubes',
        ]);

        return (new MailMessage)
            ->subject('Factura de tu suscripción - '.$number)
            ->greeting('Hola '.($notifiable->nombre ?? ''))
            ->line('Adjuntamos la factura correspondiente a tu suscripción.')
            ->line('Número de factura: '.$number)
            ->line('Total: '.$total)
            ->attachData($pdf, $filename, [
                'mime' => 'application/pdf',
            ])
            ->line('Gracias por confiar en nosotros.');
    }
}

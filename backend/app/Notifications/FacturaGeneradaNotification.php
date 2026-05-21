<?php

namespace App\Notifications;

use App\Support\BrandLogo;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Laravel\Cashier\Invoice;

class FacturaGeneradaNotification extends Notification
{
    use Queueable;

    public function __construct(public Invoice $invoice) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $number = $this->invoice->asStripeInvoice()->number ?? $this->invoice->asStripeInvoice()->id;
        $total = $this->invoice->total();

        $filename = 'factura-' . ($number ?? 'suscripcion') . '.pdf';

        $pdf = $this->invoice->pdf([
            'vendor' => BrandLogo::name(),
            'product' => 'Suscripción Stride365',
        ]);

        return (new MailMessage)
            ->subject('Factura de tu suscripción - ' . $number)
            ->view('mail.factura-generada', [
                'notifiable' => $notifiable,
                'number' => $number,
                'total' => $total,
            ])
            ->attachData($pdf, $filename, [
                'mime' => 'application/pdf',
            ]);
    }
}

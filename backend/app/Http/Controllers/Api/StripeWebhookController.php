<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Notifications\FacturaGeneradaNotification;
use Illuminate\Http\Request;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    public function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $invoiceData = $payload['data']['object'] ?? [];
        $customerId = $invoiceData['customer'] ?? null;
        $invoiceId = $invoiceData['id'] ?? null;

        if (! $customerId || ! $invoiceId) {
            return $this->successMethod();
        }

        $user = User::where('stripe_id', $customerId)->first();

        if ($user) {
            $invoice = $user->findInvoice($invoiceId);

            if ($invoice) {
                $user->notify(new FacturaGeneradaNotification($invoice));
            }
        }

        return $this->successMethod();
    }
}

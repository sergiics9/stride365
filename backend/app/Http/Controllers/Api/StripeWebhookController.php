<?php

namespace App\Http\Controllers\Api;

use App\Models\ClubUser;
use App\Models\User;
use App\Notifications\FacturaGeneradaNotification;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Laravel\Cashier\Subscription;
use Symfony\Component\HttpFoundation\Response;

class StripeWebhookController extends CashierWebhookController
{
    // Tras que Cashier persista la suscripción, reflejamos el estado en club_user.
    public function handleCustomerSubscriptionCreated(array $payload): Response
    {
        parent::handleCustomerSubscriptionCreated($payload);
        $this->syncMembership($payload);

        return $this->successMethod();
    }

    public function handleCustomerSubscriptionUpdated(array $payload): Response
    {
        parent::handleCustomerSubscriptionUpdated($payload);
        $this->syncMembership($payload);

        return $this->successMethod();
    }

    public function handleCustomerSubscriptionDeleted(array $payload): Response
    {
        parent::handleCustomerSubscriptionDeleted($payload);
        $this->syncMembership($payload, deleted: true);

        return $this->successMethod();
    }

    public function handleInvoicePaymentSucceeded(array $payload): Response
    {
        $invoiceData = $payload['data']['object'] ?? [];
        $customerId = $invoiceData['customer'] ?? null;
        $invoiceId = $invoiceData['id'] ?? null;
        $subscriptionId = $invoiceData['subscription'] ?? null;

        // Renovar current_period_end y activar membresías pending tras el cobro.
        if ($subscriptionId) {
            $local = Subscription::where('stripe_id', $subscriptionId)->first();
            if ($local) {
                ClubUser::syncFromCashierSubscription($local);
            }
        }

        // Email con PDF de factura (en local solo llega si stripe listen está activo).
        if ($customerId && $invoiceId) {
            $user = User::where('stripe_id', $customerId)->first();
            if ($user) {
                $invoice = $user->findInvoice($invoiceId);
                if ($invoice) {
                    $user->notify(new FacturaGeneradaNotification($invoice));
                }
            }
        }

        return $this->successMethod();
    }

    private function syncMembership(array $payload, bool $deleted = false): void
    {
        $object = $payload['data']['object'] ?? [];
        $stripeId = $object['id'] ?? null;
        if (! $stripeId) {
            return;
        }

        $local = Subscription::where('stripe_id', $stripeId)->first();
        if (! $local) {
            return;
        }

        ClubUser::syncFromCashierSubscription($local, $deleted);
    }
}

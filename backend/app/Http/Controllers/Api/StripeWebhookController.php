<?php

namespace App\Http\Controllers\Api;

use App\Models\Club;
use App\Models\ClubUser;
use App\Models\User;
use App\Notifications\FacturaGeneradaNotification;
use Carbon\Carbon;
use Laravel\Cashier\Http\Controllers\WebhookController as CashierWebhookController;
use Laravel\Cashier\Subscription;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class StripeWebhookController extends CashierWebhookController
{
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

        if ($subscriptionId) {
            $local = Subscription::where('stripe_id', $subscriptionId)->first();
            if ($local) {
                $this->syncMembershipFromLocalSubscription($local);
            }
        }

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

        $this->syncMembershipFromLocalSubscription($local, $deleted ? $object : null);
    }

    private function syncMembershipFromLocalSubscription(Subscription $local, ?array $deletedObject = null): void
    {
        $parsed = ClubUser::parseSubscriptionName($local->name);
        if (! $parsed) {
            return;
        }

        $club = Club::find($parsed['club_id']);
        if (! $club) {
            return;
        }

        $membership = ClubUser::firstOrCreate(
            [
                'user_id' => $local->user_id,
                'club_id' => $parsed['club_id'],
                'role' => $parsed['role'],
            ],
            [
                'status' => ClubUser::STATUS_PENDING,
                'subscription_name' => $local->name,
                'joined_at' => now()->toDateString(),
            ]
        );

        $status = $this->mapStatus($local, (bool) $deletedObject);

        $stripePeriodEnd = null;
        try {
            $stripeSub = $local->asStripeSubscription();
            $ts = $stripeSub->current_period_end
                ?? ($stripeSub->items->data[0]->current_period_end ?? null);
            if ($ts) {
                $stripePeriodEnd = Carbon::createFromTimestamp($ts);
            }
        } catch (Throwable $e) {
            $stripePeriodEnd = null;
        }

        $membership->fill([
            'subscription_name' => $local->name,
            'stripe_subscription_id' => $local->stripe_id,
            'status' => $status,
            'subscribed_at' => $membership->subscribed_at ?? now(),
            'current_period_end' => $stripePeriodEnd,
            'ends_at' => $local->ends_at,
        ])->save();
    }

    private function mapStatus(Subscription $subscription, bool $deleted): string
    {
        if ($deleted) {
            return ClubUser::STATUS_INACTIVE;
        }
        if ($subscription->onGracePeriod()) {
            return ClubUser::STATUS_GRACE;
        }
        if ($subscription->canceled() && ! $subscription->onGracePeriod()) {
            return ClubUser::STATUS_CANCELLED;
        }
        if (in_array($subscription->stripe_status, ['active', 'trialing'], true)) {
            return ClubUser::STATUS_ACTIVE;
        }

        return ClubUser::STATUS_INACTIVE;
    }
}

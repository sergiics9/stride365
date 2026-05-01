<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        $user = $request->user();

        $subscription = $user->subscription('default');

        $currentPeriodEnd = null;
        if ($subscription && $subscription->stripe_status === 'active') {
            try {
                $stripeSub = $subscription->asStripeSubscription();
                $periodEnd = $stripeSub->current_period_end
                    ?? ($stripeSub->items->data[0]->current_period_end ?? null);
                if ($periodEnd) {
                    $currentPeriodEnd = \Carbon\Carbon::createFromTimestamp($periodEnd)->toIso8601String();
                }
            } catch (\Throwable $e) {
                $currentPeriodEnd = null;
            }
        }

        return response()->json([
            'subscribed' => $user->subscribed('default'),
            'on_trial' => $user->onTrial('default'),
            'on_grace_period' => $subscription?->onGracePeriod() ?? false,
            'cancelled' => $subscription?->canceled() ?? false,
            'ends_at' => $subscription?->ends_at,
            'current_period_end' => $currentPeriodEnd,
            'stripe_status' => $subscription?->stripe_status,
            'stripe_price' => $subscription?->stripe_price,
        ]);
    }

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'price_id' => ['required', 'string'],
            'success_url' => ['required', 'url'],
            'cancel_url' => ['required', 'url'],
        ]);

        $user = $request->user();
        $user->createOrGetStripeCustomer();

        $separator = str_contains($validated['success_url'], '?') ? '&' : '?';

        $checkout = $user
            ->newSubscription('default', $validated['price_id'])
            ->checkout([
                'success_url' => $validated['success_url'].$separator.'session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $validated['cancel_url'],
            ]);

        return response()->json([
            'id' => $checkout->id,
            'url' => $checkout->url,
        ]);
    }

    public function cancel(Request $request): JsonResponse
    {
        $user = $request->user();

        if (! $user->subscribed('default')) {
            return response()->json(['message' => 'No hay suscripción activa.'], 404);
        }

        $user->subscription('default')->cancel();

        return response()->json([
            'message' => 'Suscripción cancelada al final del periodo.',
            'ends_at' => $user->subscription('default')->ends_at,
        ]);
    }

    public function resume(Request $request): JsonResponse
    {
        $user = $request->user();
        $subscription = $user->subscription('default');

        if (! $subscription || ! $subscription->onGracePeriod()) {
            return response()->json(['message' => 'No hay suscripción reanudable.'], 422);
        }

        $subscription->resume();

        return response()->json(['message' => 'Suscripción reanudada.']);
    }

    public function invoices(Request $request): JsonResponse
    {
        $user = $request->user();

        $invoices = $user->invoices()->map(fn ($invoice) => [
            'id' => $invoice->id,
            'number' => $invoice->number,
            'total' => $invoice->total(),
            'date' => $invoice->date()?->toDateTimeString(),
            'download_url' => route('api.subscription.invoices.download', ['invoice' => $invoice->id]),
        ]);

        return response()->json($invoices);
    }

    public function downloadInvoice(Request $request, string $invoice)
    {
        return $request->user()->downloadInvoice($invoice, [
            'vendor' => config('app.name'),
            'product' => 'Suscripción módulo Clubes',
        ]);
    }
}

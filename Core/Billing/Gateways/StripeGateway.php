<?php
declare(strict_types=1);

namespace Core\Billing\Gateways;

/**
 * StripeGateway — STUB, for card payments (useful for tenants outside Kenya,
 * or as a second option alongside M-Pesa). Not wired into the container.
 *
 * To activate:
 *   1. composer require stripe/stripe-php
 *   2. Fill in charge() to create a Stripe Checkout Session (or PaymentIntent)
 *      for $request->amount / $request->currency, return
 *      PaymentResult::pending($sessionId).
 *   3. Add a POST /billing/webhooks/stripe route that verifies the Stripe
 *      signature and calls SubscriptionBillingService::confirmPayment().
 *   4. Register in bootstrap/container.php.
 *
 * Same seam as MpesaGateway — Modules\Billing is untouched either way.
 */
final class StripeGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'stripe';
    }

    public function charge(PaymentRequest $request): PaymentResult
    {
        throw new \RuntimeException(
            'StripeGateway is a stub. Implement the Stripe Checkout call before using it — see class docblock.'
        );
    }
}

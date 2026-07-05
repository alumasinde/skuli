<?php
declare(strict_types=1);

namespace Core\Billing\Gateways;

/**
 * PaymentGatewayInterface — every gateway (manual, M-Pesa Daraja, Stripe)
 * implements this. The billing service depends only on this interface, never
 * on a concrete gateway, so adding Stripe later means writing one new class
 * and registering it in the container — nothing in Modules\Billing changes.
 *
 * charge() is intentionally synchronous-looking even though real gateways
 * (M-Pesa STK push, Stripe Checkout) are asynchronous in practice: the
 * gateway returns a PENDING result immediately, and a separate webhook/
 * callback endpoint later calls SubscriptionBillingService::confirmPayment()
 * to move the payment from pending to succeeded. See ManualGateway for the
 * simplest case (synchronous, no webhook needed) and the docblock on
 * PaymentResult for the async contract.
 */
interface PaymentGatewayInterface
{
    /** Machine name used in subscription_payments.gateway, e.g. 'manual', 'mpesa', 'stripe'. */
    public function name(): string;

    /**
     * Initiate a charge for an invoice. For synchronous gateways (manual
     * entry) this returns a final result. For asynchronous gateways (M-Pesa
     * STK push, Stripe redirect) this returns a PENDING result, and the
     * caller is responsible for reconciling later via the webhook handler.
     */
    public function charge(PaymentRequest $request): PaymentResult;
}

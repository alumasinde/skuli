<?php
declare(strict_types=1);

namespace Core\Billing\Gateways;

/**
 * MpesaGateway — STUB. Not wired into the container or used anywhere yet.
 * This exists to prove the interface holds up for a real async gateway
 * before you build one, and to give you the exact shape to fill in.
 *
 * Your project already has M-Pesa STK push infrastructure for student fee
 * collection (see pending_mpesa_pushes / Modules\Finance) — this would reuse
 * the same Daraja credentials and callback pattern, just against
 * subscription_payments instead of fee_payments.
 *
 * To activate:
 *   1. Fill in charge() to call Daraja's STK push API with $request->phone
 *      and $request->amount, using DARAJA_* env vars (add these to .env,
 *      following the same pattern as your existing Finance module's M-Pesa
 *      config in school_settings.mpesa_paybill/mpesa_account).
 *   2. Store the CheckoutRequestID Daraja returns as the gatewayRef and
 *      return PaymentResult::pending($checkoutRequestId).
 *   3. Add a POST /billing/webhooks/mpesa route (unauthenticated, IP-
 *      allowlisted to Safaricom's callback IPs) that receives the callback
 *      and calls SubscriptionBillingService::confirmPayment($gatewayRef, ...).
 *   4. Register this class in bootstrap/container.php and swap it in for
 *      ManualGateway wherever a tenant pays by M-Pesa instead of bank/cash.
 *
 * Nothing in Modules\Billing needs to change for any of this — that's the
 * point of depending on PaymentGatewayInterface rather than a concrete class.
 */
final class MpesaGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'mpesa';
    }

    public function charge(PaymentRequest $request): PaymentResult
    {
        throw new \RuntimeException(
            'MpesaGateway is a stub. Implement the Daraja STK push call before using it — see class docblock.'
        );
    }
}

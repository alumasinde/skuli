<?php
declare(strict_types=1);

namespace Core\Billing\Gateways;

/**
 * PaymentResult — what a gateway hands back after charge().
 *
 * status:
 *   'succeeded' — money confirmed received right now (manual entry, or a
 *                 gateway that confirms synchronously).
 *   'pending'   — charge initiated but not yet confirmed (M-Pesa STK push
 *                 sent to the phone, Stripe checkout session created). The
 *                 webhook handler later calls back in with the same
 *                 gatewayRef to move this to succeeded/failed.
 *   'failed'    — charge attempt was rejected outright.
 */
final class PaymentResult
{
    public function __construct(
        public readonly string $status,       // 'succeeded' | 'pending' | 'failed'
        public readonly ?string $gatewayRef = null,
        public readonly ?string $message = null,
    ) {}

    public static function succeeded(?string $gatewayRef = null, ?string $message = null): self
    {
        return new self('succeeded', $gatewayRef, $message);
    }

    public static function pending(?string $gatewayRef = null, ?string $message = null): self
    {
        return new self('pending', $gatewayRef, $message);
    }

    public static function failed(?string $message = null): self
    {
        return new self('failed', null, $message);
    }
}

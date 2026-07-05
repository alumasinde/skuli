<?php
declare(strict_types=1);

namespace Core\Billing\Gateways;

/**
 * PaymentRequest — everything a gateway needs to attempt a charge. A plain
 * value object rather than passing a raw array around, so every gateway
 * implementation gets typed, IDE-checked fields instead of guessing keys.
 */
final class PaymentRequest
{
    public function __construct(
        public readonly int $invoiceId,
        public readonly int $tenantId,
        public readonly float $amount,
        public readonly string $currency,
        public readonly ?string $phone = null,   // required for M-Pesa
        public readonly ?string $email = null,   // required for Stripe
        public readonly ?int $recordedBy = null, // super admin user id, for manual entries
        public readonly ?string $notes = null,
    ) {}
}

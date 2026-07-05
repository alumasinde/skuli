<?php
declare(strict_types=1);

namespace Core\Billing\Gateways;

/**
 * ManualGateway — records a payment the super admin already collected offline
 * (bank transfer, cash, cheque). This is the only gateway wired up today.
 * It's synchronous: there's no external system to wait on, so it always
 * returns 'succeeded' immediately.
 */
final class ManualGateway implements PaymentGatewayInterface
{
    public function name(): string
    {
        return 'manual';
    }

    public function charge(PaymentRequest $request): PaymentResult
    {
        // Nothing to call out to — the super admin is asserting "this money
        // has already arrived." Validation (amount > 0 etc.) happens in the
        // billing service before this is ever invoked.
        return PaymentResult::succeeded(
            gatewayRef: null,
            message: 'Recorded manually.'
        );
    }
}

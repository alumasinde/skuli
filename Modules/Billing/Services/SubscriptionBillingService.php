<?php
declare(strict_types=1);

namespace Modules\Billing\Services;

use Core\AuditLogger;
use Core\Billing\Gateways\PaymentGatewayInterface;
use Core\Billing\Gateways\PaymentRequest;
use Modules\Billing\Repositories\PlanRepository;
use Modules\Billing\Repositories\SubscriptionRepository;

/**
 * SubscriptionBillingService — owns subscription lifecycle + invoicing.
 * Depends on PaymentGatewayInterface, not a concrete gateway — see
 * Core\Billing\Gateways for the swap point when M-Pesa/Stripe are added.
 */
final class SubscriptionBillingService
{
    public function __construct(
        private readonly SubscriptionRepository $repo,
        private readonly PlanRepository $plans,
        private readonly PaymentGatewayInterface $gateway,
        private readonly AuditLogger $audit
    ) {}

    public function listPlans(): array
    {
        return $this->plans->listActive();
    }

    /**
     * Start a subscription for a tenant — called right after
     * TenantProvisioningService::provision() (Phase 1), or later by a
     * self-service signup flow (Phase 3). Defaults to a 14-day trial;
     * pass $trialDays = 0 for an immediate paid start.
     */
    public function subscribe(int $tenantId, string $planCode, string $billingCycle = 'monthly', int $trialDays = 14): array
    {
        $plan = $this->plans->findByCode($planCode);
        if (!$plan) {
            throw new \InvalidArgumentException("Unknown plan \"{$planCode}\".");
        }
        if (!in_array($billingCycle, ['monthly', 'yearly'], true)) {
            throw new \InvalidArgumentException('Billing cycle must be monthly or yearly.');
        }
        if ($this->repo->findActiveForTenant($tenantId)) {
            throw new \RuntimeException('This tenant already has an active subscription.');
        }

        $now = new \DateTimeImmutable();
        $periodEnd = $billingCycle === 'yearly' ? $now->modify('+1 year') : $now->modify('+1 month');
        $trialEnd  = $trialDays > 0 ? $now->modify("+{$trialDays} days") : null;

        $subId = $this->repo->create([
            'tenant_id'             => $tenantId,
            'plan_id'               => $plan['id'],
            'billing_cycle'         => $billingCycle,
            'status'                => $trialDays > 0 ? 'trialing' : 'active',
            'trial_ends_at'         => $trialEnd?->format('Y-m-d H:i:s'),
            'current_period_start'  => $now->format('Y-m-d H:i:s'),
            'current_period_end'    => $periodEnd->format('Y-m-d H:i:s'),
        ]);

        $this->audit->log('subscribe', 'subscription', $subId, ['tenant_id' => $tenantId, 'plan' => $planCode]);

        // Only invoice immediately if there's no trial — a trialing
        // subscription gets its first invoice when the trial converts.
        if ($trialDays === 0) {
            $this->generateInvoiceForSubscription($subId);
        }

        return $this->repo->findById($subId);
    }

    public function getActiveForTenant(int $tenantId): ?array
    {
        return $this->repo->findActiveForTenant($tenantId);
    }

    /**
     * Generate an invoice for the current billing period of a subscription.
     * Idempotent in spirit (called by renew() at period boundaries) — invoice
     * numbers are sequential per calendar year: INV-2026-000123.
     */
    public function generateInvoiceForSubscription(int $subscriptionId): array
    {
        $sub = $this->repo->findById($subscriptionId);
        if (!$sub) {
            throw new \InvalidArgumentException('Subscription not found.');
        }

        $amount = $sub['billing_cycle'] === 'yearly' ? $sub['price_yearly'] : $sub['price_monthly'];
        $year   = date('Y');
        $seq    = $this->repo->lastInvoiceSequenceForYear($year) + 1;
        $invoiceNo = sprintf('INV-%s-%06d', $year, $seq);

        $dueDate = (new \DateTimeImmutable($sub['current_period_start']))->modify('+7 days')->format('Y-m-d');

        $invoiceId = $this->repo->createInvoice([
            'tenant_id'       => $sub['tenant_id'],
            'subscription_id' => $subscriptionId,
            'invoice_no'      => $invoiceNo,
            'amount'          => $amount,
            'currency'        => $sub['currency'],
            'status'          => (float) $amount === 0.0 ? 'paid' : 'open', // free plan auto-settles
            'period_start'    => $sub['current_period_start'],
            'period_end'      => $sub['current_period_end'],
            'due_date'        => $dueDate,
        ]);

        if ((float) $amount === 0.0) {
            $this->repo->markInvoicePaid($invoiceId);
        }

        $this->audit->log('invoice_generated', 'subscription_invoice', $invoiceId, [
            'subscription_id' => $subscriptionId, 'amount' => $amount, 'invoice_no' => $invoiceNo,
        ]);

        return $this->repo->findInvoiceById($invoiceId);
    }

    /**
     * Record a payment against an invoice. Uses whatever gateway was injected
     * (ManualGateway today). For an async gateway (M-Pesa/Stripe) this call
     * initiates the charge and the payment sits at 'pending' until
     * confirmPayment() is called from a webhook.
     */
    public function pay(int $invoiceId, float $amount, ?int $recordedBy = null, ?string $notes = null, ?string $phone = null): array
    {
        $invoice = $this->repo->findInvoiceById($invoiceId);
        if (!$invoice) {
            throw new \InvalidArgumentException('Invoice not found.');
        }
        if ($invoice['status'] === 'paid') {
            throw new \RuntimeException('Invoice is already paid.');
        }
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $result = $this->gateway->charge(new PaymentRequest(
            invoiceId: $invoiceId,
            tenantId: (int) $invoice['tenant_id'],
            amount: $amount,
            currency: $invoice['currency'],
            phone: $phone,
            recordedBy: $recordedBy,
            notes: $notes,
        ));

        if ($result->status === 'failed') {
            throw new \RuntimeException($result->message ?? 'Payment failed.');
        }

        $paymentId = $this->repo->createPayment([
            'tenant_id'   => $invoice['tenant_id'],
            'invoice_id'  => $invoiceId,
            'amount'      => $amount,
            'currency'    => $invoice['currency'],
            'gateway'     => $this->gateway->name(),
            'gateway_ref' => $result->gatewayRef,
            'status'      => $result->status, // 'succeeded' or 'pending'
            'recorded_by' => $recordedBy,
            'notes'       => $notes,
        ]);

        if ($result->status === 'succeeded') {
            $this->settleInvoice($invoiceId);
        }

        $this->audit->log('payment_recorded', 'subscription_payment', $paymentId, [
            'invoice_id' => $invoiceId, 'amount' => $amount, 'gateway' => $this->gateway->name(), 'status' => $result->status,
        ]);

        return $this->repo->findInvoiceById($invoiceId);
    }

    /**
     * Called from a gateway webhook once an async payment is confirmed.
     * Not used by ManualGateway (which settles synchronously in pay()) —
     * this is the entry point MpesaGateway/StripeGateway's callback routes
     * would call.
     */
    public function confirmPayment(string $gatewayRef, bool $succeeded): void
    {
        $payment = $this->repo->findPaymentByGatewayRef($gatewayRef);
        if (!$payment) {
            throw new \InvalidArgumentException("No payment found for gateway ref \"{$gatewayRef}\".");
        }

        $this->repo->updatePaymentStatus((int) $payment['id'], $succeeded ? 'succeeded' : 'failed');

        if ($succeeded) {
            $this->settleInvoice((int) $payment['invoice_id']);
        }

        $this->audit->log('payment_confirmed', 'subscription_payment', (int) $payment['id'], [
            'gateway_ref' => $gatewayRef, 'succeeded' => $succeeded,
        ]);
    }

    private function settleInvoice(int $invoiceId): void
    {
        $invoice = $this->repo->findInvoiceById($invoiceId);
        if (!$invoice) {
            return;
        }
        $this->repo->markInvoicePaid($invoiceId);

        // Paying the invoice for a trialing subscription converts it to active.
        $sub = $this->repo->findById((int) $invoice['subscription_id']);
        if ($sub && $sub['status'] === 'trialing') {
            $this->repo->updateStatus((int) $sub['id'], 'active');
        }
    }

    public function cancel(int $subscriptionId, ?string $reason = null): void
    {
        $this->repo->cancel($subscriptionId, $reason);
        $this->audit->log('cancel', 'subscription', $subscriptionId, ['reason' => $reason]);
    }

    public function listInvoicesForTenant(int $tenantId): array
    {
        return $this->repo->listInvoicesForTenant($tenantId);
    }

    public function listPaymentsForTenant(int $tenantId): array
    {
        return $this->repo->listPaymentsForTenant($tenantId);
    }

    /** Cross-tenant "who owes money" view for the super admin. */
    public function listOpenInvoices(): array
    {
        return $this->repo->listOpenInvoices();
    }

    public function totalRevenue(): float
    {
        return $this->repo->totalRevenueSucceeded();
    }

    /**
     * Renewal/dunning sweep — intended to run as a daily cron job
     * (php bin/console billing:renew, or a simple cron hitting a CLI script).
     * For each subscription whose period has ended: generate the next
     * invoice and roll the period forward. Marks past_due if the PRIOR
     * invoice for that subscription was never paid.
     */
    public function runRenewalSweep(\DateTimeImmutable $asOf = new \DateTimeImmutable()): array
    {
        $expiring = $this->repo->findExpiring($asOf->format('Y-m-d H:i:s'));
        $results = [];

        foreach ($expiring as $sub) {
            $subId = (int) $sub['id'];

            $openInvoices = array_filter(
                $this->repo->listInvoicesForTenant((int) $sub['tenant_id']),
                static fn ($inv) => (int) $inv['subscription_id'] === $subId && $inv['status'] === 'open'
            );

            if (!empty($openInvoices)) {
                // Previous period's invoice was never paid — flag, don't renew.
                $this->repo->updateStatus($subId, 'past_due');
                $results[] = ['subscription_id' => $subId, 'action' => 'marked_past_due'];
                continue;
            }

            $cycle = $sub['billing_cycle'];
            $newStart = new \DateTimeImmutable($sub['current_period_end']);
            $newEnd   = $cycle === 'yearly' ? $newStart->modify('+1 year') : $newStart->modify('+1 month');

            $this->repo->renewPeriod($subId, $newStart->format('Y-m-d H:i:s'), $newEnd->format('Y-m-d H:i:s'));
            $this->generateInvoiceForSubscription($subId);

            $results[] = ['subscription_id' => $subId, 'action' => 'renewed'];
        }

        return $results;
    }
}

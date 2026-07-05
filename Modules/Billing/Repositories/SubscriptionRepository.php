<?php
declare(strict_types=1);

namespace Modules\Billing\Repositories;

use Core\Repository;

final class SubscriptionRepository extends Repository
{
    // ── Subscriptions ────────────────────────────────────────────────────────

    public function create(array $d): int
    {
        return $this->insert('
            INSERT INTO subscriptions
                (tenant_id, plan_id, billing_cycle, status, trial_ends_at, current_period_start, current_period_end)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ', [
            $d['tenant_id'], $d['plan_id'], $d['billing_cycle'], $d['status'],
            $d['trial_ends_at'] ?? null, $d['current_period_start'], $d['current_period_end'],
        ]);
    }

    public function findActiveForTenant(int $tenantId): ?array
    {
        // "Active" here covers both trialing and active — anything that isn't
        // cancelled/expired/past_due, i.e. the subscription currently in force.
        return $this->fetchOne('
            SELECT s.*, p.code AS plan_code, p.name AS plan_name,
                   p.price_monthly, p.price_yearly, p.currency, p.max_students, p.max_staff, p.features
            FROM subscriptions s
            JOIN plans p ON p.id = s.plan_id
            WHERE s.tenant_id = ? AND s.status IN (\'trialing\',\'active\',\'past_due\') AND s.deleted_at IS NULL
            ORDER BY s.created_at DESC
            LIMIT 1
        ', [$tenantId]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('
            SELECT s.*, p.code AS plan_code, p.name AS plan_name, p.price_monthly, p.price_yearly, p.currency
            FROM subscriptions s
            JOIN plans p ON p.id = s.plan_id
            WHERE s.id = ? AND s.deleted_at IS NULL
        ', [$id]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->execute('UPDATE subscriptions SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function cancel(int $id, ?string $reason): void
    {
        $this->execute(
            'UPDATE subscriptions SET status = \'cancelled\', cancelled_at = NOW(), cancel_reason = ? WHERE id = ?',
            [$reason, $id]
        );
    }

    public function renewPeriod(int $id, string $start, string $end): void
    {
        $this->execute(
            'UPDATE subscriptions SET status = \'active\', current_period_start = ?, current_period_end = ? WHERE id = ?',
            [$start, $end, $id]
        );
    }

    /** Subscriptions whose current period has ended — used by the renewal/dunning job. */
    public function findExpiring(string $asOf): array
    {
        return $this->fetchAll('
            SELECT * FROM subscriptions
            WHERE status IN (\'active\',\'trialing\') AND current_period_end <= ? AND deleted_at IS NULL
        ', [$asOf]);
    }

    // ── Invoices ─────────────────────────────────────────────────────────────

    public function createInvoice(array $d): int
    {
        return $this->insert('
            INSERT INTO subscription_invoices
                (tenant_id, subscription_id, invoice_no, amount, currency, status, period_start, period_end, due_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            $d['tenant_id'], $d['subscription_id'], $d['invoice_no'], $d['amount'], $d['currency'],
            $d['status'] ?? 'open', $d['period_start'], $d['period_end'], $d['due_date'],
        ]);
    }

    public function findInvoiceById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM subscription_invoices WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public function listInvoicesForTenant(int $tenantId): array
    {
        return $this->fetchAll('
            SELECT * FROM subscription_invoices
            WHERE tenant_id = ? AND deleted_at IS NULL
            ORDER BY period_start DESC
        ', [$tenantId]);
    }

    public function listOpenInvoices(): array
    {
        // Cross-tenant view for the super admin "who owes money" screen.
        return $this->fetchAll('
            SELECT si.*, t.name AS tenant_name, t.slug AS tenant_slug
            FROM subscription_invoices si
            JOIN tenants t ON t.id = si.tenant_id
            WHERE si.status = \'open\' AND si.deleted_at IS NULL
            ORDER BY si.due_date ASC
        ');
    }

    public function markInvoicePaid(int $id): void
    {
        $this->execute('UPDATE subscription_invoices SET status = \'paid\', paid_at = NOW() WHERE id = ?', [$id]);
    }

    public function markInvoiceVoid(int $id): void
    {
        $this->execute('UPDATE subscription_invoices SET status = \'void\' WHERE id = ?', [$id]);
    }

    /** Highest sequence number used so far this year, for invoice numbering. */
    public function lastInvoiceSequenceForYear(string $year): int
    {
        $prefix = "INV-{$year}-";
        $last = $this->fetchColumn(
            'SELECT invoice_no FROM subscription_invoices WHERE invoice_no LIKE ? ORDER BY id DESC LIMIT 1',
            [$prefix . '%']
        );
        if (!$last) {
            return 0;
        }
        return (int) substr((string) $last, strlen($prefix));
    }

    // ── Payments ─────────────────────────────────────────────────────────────

    public function createPayment(array $d): int
    {
        return $this->insert('
            INSERT INTO subscription_payments
                (tenant_id, invoice_id, amount, currency, gateway, gateway_ref, status, recorded_by, notes, paid_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            $d['tenant_id'], $d['invoice_id'], $d['amount'], $d['currency'],
            $d['gateway'], $d['gateway_ref'] ?? null, $d['status'], $d['recorded_by'] ?? null,
            $d['notes'] ?? null, $d['paid_at'] ?? date('Y-m-d H:i:s'),
        ]);
    }

    public function findPaymentByGatewayRef(string $ref): ?array
    {
        return $this->fetchOne('SELECT * FROM subscription_payments WHERE gateway_ref = ?', [$ref]);
    }

    public function updatePaymentStatus(int $id, string $status): void
    {
        $this->execute('UPDATE subscription_payments SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function listPaymentsForTenant(int $tenantId): array
    {
        return $this->fetchAll('
            SELECT * FROM subscription_payments WHERE tenant_id = ? ORDER BY paid_at DESC
        ', [$tenantId]);
    }

    public function totalRevenueSucceeded(): float
    {
        return (float) $this->fetchColumn(
            "SELECT COALESCE(SUM(amount), 0) FROM subscription_payments WHERE status = 'succeeded'",
            []
        );
    }
}

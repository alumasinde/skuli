<?php
declare(strict_types=1);

namespace Modules\SuperAdmin\Controllers\Web;

use Core\Session;
use Core\WebController;
use Modules\Billing\Services\SubscriptionBillingService;
use Modules\Tenants\Services\TenantProvisioningService;

/**
 * SuperAdminBillingController — cross-tenant billing view: who's on what
 * plan, who owes money, and a way to record a payment the super admin
 * collected offline (bank transfer, cash at a sales meeting, etc.) against
 * any tenant's invoice.
 */
final class SuperAdminBillingController extends WebController
{
    public function __construct(
        private SubscriptionBillingService $billing,
        private TenantProvisioningService $tenants
    ) {
        parent::__construct();
    }

    private function requireSuperAdmin(): void
    {
        if (!Session::hasRole('superadmin')) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Access Denied', 'message' => 'Super admin access required.']);
            exit;
        }
    }

    /** GET /super-admin/billing — revenue snapshot + open invoices across all tenants. */
    public function index(array $params): void
    {
        $this->requireSuperAdmin();

        $this->view('super_admin/billing/index', [
            'title'        => 'Billing',
            'openInvoices' => $this->billing->listOpenInvoices(),
            'totalRevenue' => $this->billing->totalRevenue(),
        ]);
    }

    /** GET /super-admin/billing/tenants/{id} — one tenant's subscription + invoice history. */
    public function tenantDetail(array $params): void
    {
        $this->requireSuperAdmin();

        $tenantId = (int) ($params['id'] ?? 0);
        $tenant   = $this->tenants->getById($tenantId);
        if (!$tenant) {
            $this->redirect('/super-admin/tenants', 'Tenant not found.', 'error');
            return;
        }

        $this->view('super_admin/billing/tenant_detail', [
            'title'       => 'Billing — ' . $tenant['name'],
            'tenant'      => $tenant,
            'subscription'=> $this->billing->getActiveForTenant($tenantId),
            'invoices'    => $this->billing->listInvoicesForTenant($tenantId),
            'payments'    => $this->billing->listPaymentsForTenant($tenantId),
            'plans'       => $this->billing->listPlans(),
            'errors'      => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /super-admin/billing/tenants/{id}/subscribe — start a subscription for a tenant. */
    public function subscribe(array $params): void
    {
        $this->requireSuperAdmin();
        $tenantId = (int) ($params['id'] ?? 0);

        try {
            $this->billing->subscribe(
                $tenantId,
                $_POST['plan_code'] ?? '',
                $_POST['billing_cycle'] ?? 'monthly',
                (int) ($_POST['trial_days'] ?? 14)
            );
            $this->redirect("/super-admin/billing/tenants/{$tenantId}", 'Subscription started.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/super-admin/billing/tenants/{$tenantId}");
        }
    }

    /** POST /super-admin/billing/invoices/{id}/pay — record an offline payment. */
    public function recordPayment(array $params): void
    {
        $this->requireSuperAdmin();
        $invoiceId = (int) ($params['id'] ?? 0);
        $tenantId  = (int) ($_POST['tenant_id'] ?? 0);

        try {
            $this->billing->pay(
                $invoiceId,
                (float) ($_POST['amount'] ?? 0),
                \Core\RequestContext::userId(),
                trim($_POST['notes'] ?? '') ?: null
            );
            $this->redirect("/super-admin/billing/tenants/{$tenantId}", 'Payment recorded.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/super-admin/billing/tenants/{$tenantId}");
        }
    }

    /** POST /super-admin/billing/subscriptions/{id}/cancel */
    public function cancelSubscription(array $params): void
    {
        $this->requireSuperAdmin();
        $subId    = (int) ($params['id'] ?? 0);
        $tenantId = (int) ($_POST['tenant_id'] ?? 0);

        $this->billing->cancel($subId, trim($_POST['reason'] ?? '') ?: null);
        $this->redirect("/super-admin/billing/tenants/{$tenantId}", 'Subscription cancelled.');
    }
}

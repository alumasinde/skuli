<?php
declare(strict_types=1);

namespace Modules\Billing\Controllers\Web;

use Core\RequestContext;
use Core\WebController;
use Modules\Billing\Services\SubscriptionBillingService;

/**
 * BillingWebController — what a tenant's own admin sees: their current plan,
 * invoice history, payment history. Read-only today (no self-service upgrade/
 * downgrade yet — that's a natural Phase 3 addition once online payment is
 * live, using the same SubscriptionBillingService::subscribe()/pay() this
 * controller already reads from).
 */
final class BillingWebController extends WebController
{
    public function __construct(private SubscriptionBillingService $billing)
    {
        parent::__construct();
    }

    /** GET /billing */
    public function index(array $params): void
    {
        $this->requirePermission('billing.view');

        $tenantId = RequestContext::tenantId();
        if ($tenantId === null) {
            $this->redirect('/dashboard', 'No tenant context.', 'error');
            return;
        }

        $this->view('billing/index', [
            'title'        => 'Billing',
            'subscription' => $this->billing->getActiveForTenant($tenantId),
            'invoices'     => $this->billing->listInvoicesForTenant($tenantId),
            'payments'     => $this->billing->listPaymentsForTenant($tenantId),
        ]);
    }
}

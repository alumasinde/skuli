<?php
declare(strict_types=1);

namespace Modules\SuperAdmin\Controllers\Web;

use Core\Session;
use Core\WebController;
use Modules\Marketing\Services\DemoRequestService;

/**
 * SuperAdminDemoRequestsController — the review queue that closes the loop
 * from Phase 3 back into Phase 1: a prospect fills the public /demo form,
 * it lands here, and "Approve & Provision" calls straight into
 * TenantProvisioningService (via DemoRequestService::approveAndProvision)
 * with the prospect's own details prefilled.
 */
final class SuperAdminDemoRequestsController extends WebController
{
    public function __construct(private DemoRequestService $demoRequests)
    {
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

    /** GET /super-admin/demo-requests */
    public function index(array $params): void
    {
        $this->requireSuperAdmin();

        $status = $_GET['status'] ?? null;
        $status = in_array($status, ['new', 'contacted', 'scheduled', 'approved', 'declined', 'spam'], true) ? $status : null;

        $this->view('super_admin/demo_requests/index', [
            'title'    => 'Demo Requests',
            'requests' => $this->demoRequests->list($status),
            'status'   => $status,
        ]);
    }

    /** GET /super-admin/demo-requests/{id} */
    public function show(array $params): void
    {
        $this->requireSuperAdmin();

        $req = $this->demoRequests->getById((int) ($params['id'] ?? 0));
        if (!$req) {
            $this->redirect('/super-admin/demo-requests', 'Demo request not found.', 'error');
            return;
        }

        $this->view('super_admin/demo_requests/show', [
            'title'   => 'Demo Request — ' . $req['school_name'],
            'request' => $req,
            'errors'  => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /super-admin/demo-requests/{id}/contacted */
    public function markContacted(array $params): void
    {
        $this->requireSuperAdmin();
        $id = (int) ($params['id'] ?? 0);

        $this->demoRequests->markContacted($id, \Core\RequestContext::userId(), trim($_POST['notes'] ?? '') ?: null);
        $this->redirect("/super-admin/demo-requests/{$id}", 'Marked as contacted.');
    }

    /** POST /super-admin/demo-requests/{id}/decline */
    public function decline(array $params): void
    {
        $this->requireSuperAdmin();
        $id = (int) ($params['id'] ?? 0);

        $this->demoRequests->decline($id, \Core\RequestContext::userId(), trim($_POST['notes'] ?? '') ?: null);
        $this->redirect('/super-admin/demo-requests', 'Demo request declined.');
    }

    /**
     * POST /super-admin/demo-requests/{id}/approve — the bridge into Phase 1.
     * Lets the super admin adjust the prefilled school code / admin name /
     * plan before provisioning, in case the prospect's raw submission needs
     * cleanup (e.g. school name has punctuation that shouldn't be in the code).
     */
    public function approve(array $params): void
    {
        $this->requireSuperAdmin();
        $id = (int) ($params['id'] ?? 0);

        $overrides = array_filter([
            'tenant_name'      => trim($_POST['tenant_name'] ?? ''),
            'plan'             => trim($_POST['plan'] ?? ''),
            'school_name'      => trim($_POST['school_name'] ?? ''),
            'school_code'      => trim($_POST['school_code'] ?? ''),
            'admin_first_name' => trim($_POST['admin_first_name'] ?? ''),
            'admin_last_name'  => trim($_POST['admin_last_name'] ?? ''),
            'admin_email'      => trim($_POST['admin_email'] ?? ''),
        ], static fn ($v) => $v !== '');

        try {
            $result = $this->demoRequests->approveAndProvision($id, $overrides, \Core\RequestContext::userId());
            Session::flash('provision_result', $result);
            $this->redirect('/super-admin/tenants/' . $result['tenant_id'] . '/provisioned', 'Tenant provisioned from demo request.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/super-admin/demo-requests/{$id}");
        }
    }
}

<?php
declare(strict_types=1);

namespace Modules\SuperAdmin\Controllers\Web;

use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Tenants\Services\TenantProvisioningService;

final class SuperAdminController extends WebController
{
    public function __construct(private TenantProvisioningService $tenants)
    {
        parent::__construct();
    }

    private function requireSuperAdmin(): void
    {
        if (!Session::hasRole('superadmin')) {
            http_response_code(403);
            $this->view('errors/403', [
                'title'   => 'Access Denied',
                'message' => 'Super admin access required.',
            ]);
            exit;
        }
    }

    /** GET /super-admin/tenants */
    public function index(array $params): void
    {
        $this->requireSuperAdmin();

        $this->view('super_admin/tenants/index', [
            'title'   => 'Tenants',
            'tenants' => $this->tenants->list(),
        ]);
    }

    /** GET /super-admin/tenants/create */
    public function create(array $params): void
    {
        $this->requireSuperAdmin();

        $this->view('super_admin/tenants/create', [
            'title'  => 'Provision New Tenant',
            'errors' => Session::flash('errors') ?: [],
            'old'    => Session::flash('old') ?: [],
        ]);
    }

    /** POST /super-admin/tenants — the manual-creation-after-demo workflow. */
    public function store(array $params): void
    {
        $this->requireSuperAdmin();

        $body = [
            'tenant_name'      => trim($_POST['tenant_name'] ?? ''),
            'plan'             => $_POST['plan'] ?? 'free',
            'domain'           => trim($_POST['domain'] ?? '') ?: null,
            'notes'            => trim($_POST['notes'] ?? '') ?: null,
            'school_name'      => trim($_POST['school_name'] ?? ''),
            'school_code'      => trim($_POST['school_code'] ?? ''),
            'school_email'     => trim($_POST['school_email'] ?? '') ?: null,
            'school_phone'     => trim($_POST['school_phone'] ?? '') ?: null,
            'school_type'      => $_POST['school_type'] ?? 'day',
            'school_level'     => $_POST['school_level'] ?? 'secondary',
            'admin_first_name' => trim($_POST['admin_first_name'] ?? ''),
            'admin_last_name'  => trim($_POST['admin_last_name'] ?? ''),
            'admin_email'      => trim($_POST['admin_email'] ?? ''),
        ];

        try {
            $result = $this->tenants->provision($body, RequestContext::userId());

            // Temp password is shown exactly once, via flash — never stored
            // or logged in plaintext beyond this single redirect.
            Session::flash('provision_result', $result);
            $this->redirect('/super-admin/tenants/' . $result['tenant_id'] . '/provisioned', 'Tenant provisioned.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/super-admin/tenants/create');
        }
    }

    /** GET /super-admin/tenants/{id}/provisioned — one-time credential reveal. */
    public function provisioned(array $params): void
    {
        $this->requireSuperAdmin();

        $result = Session::flash('provision_result');
        $tenant = $this->tenants->getById((int) ($params['id'] ?? 0));

        if (!$result || !$tenant) {
            $this->redirect('/super-admin/tenants');
            return;
        }

        $this->view('super_admin/tenants/provisioned', [
            'title'  => 'Tenant Provisioned',
            'tenant' => $tenant,
            'result' => $result,
        ]);
    }

    /** POST /super-admin/tenants/{id}/suspend */
    public function suspend(array $params): void
    {
        $this->requireSuperAdmin();
        $this->tenants->suspend((int) ($params['id'] ?? 0));
        $this->redirect('/super-admin/tenants', 'Tenant suspended.');
    }

    /** POST /super-admin/tenants/{id}/reactivate */
    public function reactivate(array $params): void
    {
        $this->requireSuperAdmin();
        $this->tenants->reactivate((int) ($params['id'] ?? 0));
        $this->redirect('/super-admin/tenants', 'Tenant reactivated.');
    }
}

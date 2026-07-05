<?php
declare(strict_types=1);

namespace Modules\AuditLog\Controllers\Web;

use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\AuditLog\Services\AuditLogService;

final class AuditLogWebController extends WebController
{
    public function __construct(private AuditLogService $service)
    {
        parent::__construct();
    }

    /** GET /activity-log — a school admin's own activity feed. */
    public function index(array $params): void
    {
        $this->requirePermission('settings.manage'); // reuse the existing admin-only gate; adjust if you want a dedicated 'audit.view' permission

        $schoolId = RequestContext::schoolId();
        $page     = max(1, (int) ($_GET['page'] ?? 1));

        $result = $schoolId
            ? $this->service->forSchool($schoolId, $page, 50)
            : ['entries' => [], 'total' => 0];

        $this->view('audit_log/index', [
            'title'   => 'Activity Log',
            'entries' => $result['entries'],
            'total'   => $result['total'],
            'page'    => $page,
            'perPage' => 50,
        ]);
    }

    /** GET /super-admin/activity-log — cross-tenant feed for the platform owner. */
    public function platform(array $params): void
    {
        if (!Session::hasRole('superadmin')) {
            http_response_code(403);
            $this->view('errors/403', ['title' => 'Access Denied', 'message' => 'Super admin access required.']);
            return;
        }

        $page   = max(1, (int) ($_GET['page'] ?? 1));
        $result = $this->service->forPlatform($page, 50);

        $this->view('audit_log/platform', [
            'title'   => 'Platform Activity Log',
            'entries' => $result['entries'],
            'total'   => $result['total'],
            'page'    => $page,
            'perPage' => 50,
        ]);
    }
}

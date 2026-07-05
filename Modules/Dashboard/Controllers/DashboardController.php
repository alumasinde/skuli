<?php
declare(strict_types=1);

namespace Modules\Dashboard\Controllers;

use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Students\Services\StudentService;
use Modules\Notices\Services\NoticeService;
use Modules\Terms\Services\TermService;
use Modules\Finance\Services\FinanceService;

final class DashboardController extends WebController
{
    public function __construct(
        private StudentService $studentService,
        private NoticeService $noticeService,
        private TermService $termService,
        private FinanceService $financeService
    ) {
        parent::__construct(); // hydrates RequestContext — was missing, so
                                // RequestContext::schoolId()/userId() were only
                                // ever populated by the session fallback, not
                                // the explicit RequestContext::set() every other
                                // web controller in this app relies on.
    }

    public function index(array $params): void
    {
        $roles = Session::get('roles', []);

        if (in_array('parent', $roles, true)) {
            $this->parentDashboard();
            return;
        }
        if (in_array('teacher', $roles, true)) {
            $this->teacherDashboard();
            return;
        }
        $this->adminDashboard();
    }

    private function adminDashboard(): void
    {
        $schoolId = RequestContext::schoolId();

        $studentsResult = $schoolId
            ? $this->studentService->listByAdmin($schoolId, 1, 1)
            : ['list' => [], 'total' => 0];

        $notices = $schoolId
            ? $this->noticeService->list($schoolId)
            : [];

        $term = $schoolId
            ? $this->termService->getCurrent($schoolId)
            : null;

        $this->view('dashboard/admin', [
            'title'         => 'Dashboard',
            'totalStudents' => $studentsResult['total'] ?? 0,
            'recentNotices' => array_slice($notices, 0, 5),
            'currentTerm'   => $term,
        ]);
    }

    private function teacherDashboard(): void
    {
        $schoolId = RequestContext::schoolId();

        $notices = $schoolId ? $this->noticeService->list($schoolId, 'teachers') : [];
        $term    = $schoolId ? $this->termService->getCurrent($schoolId) : null;

        $this->view('dashboard/teacher', [
            'title'         => 'Dashboard',
            'recentNotices' => array_slice($notices, 0, 5),
            'currentTerm'   => $term,
        ]);
    }

    private function parentDashboard(): void
    {
        $schoolId = RequestContext::schoolId();
        $userId   = RequestContext::userId();

        $children = $this->studentService->listByParentUser($userId);
        $notices  = $schoolId ? $this->noticeService->list($schoolId, 'parents') : [];
        $term     = $schoolId ? $this->termService->getCurrent($schoolId) : null;

        // Fee summary across children
        $feeSummary = [];
        foreach ($children as $child) {
            try {
                $feeSummary[$child['id']] = $this->financeService->getStudentStatement((int) $child['id']);
            } catch (\Throwable $e) {
                // Skip a child's statement if it errors rather than failing the whole dashboard
                $feeSummary[$child['id']] = null;
            }
        }

        $this->view('dashboard/parent', [
            'title'         => 'My Dashboard',
            'children'      => $children,
            'feeSummary'    => $feeSummary,
            'recentNotices' => array_slice($notices, 0, 5),
            'currentTerm'   => $term,
        ]);
    }
}
<?php
declare(strict_types=1);

namespace Modules\Parents\Controllers\Web;

use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Parents\Services\ParentService;
use Modules\Students\Services\StudentService;

final class ParentsWebController extends WebController
{
    public function __construct(
        private ParentService $service,
        private StudentService $students
    ) {
        parent::__construct();
    }

    /** GET /parents */
    public function index(array $params): void
    {
        $this->requirePermission('parents.view');
        $schoolId = RequestContext::schoolId();

        $this->view('parents/index', [
            'title'   => 'Parents',
            'parents' => $schoolId ? $this->service->list($schoolId) : [],
        ]);
    }

    /** GET /parents/create */
    public function create(array $params): void
    {
        $this->requirePermission('parents.create');
        $schoolId = RequestContext::schoolId();

        $this->view('parents/create', [
            'title'  => 'Add Parent',
            'users'  => $schoolId ? $this->service->linkableUsers($schoolId) : [],
            'errors' => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /parents */
    public function store(array $params): void
    {
        $this->requirePermission('parents.create');
        $schoolId = RequestContext::schoolId();
        $userId   = (int) ($_POST['user_id'] ?? 0);

        if ($userId === 0) {
            Session::flash('errors', ['Please select a user account.']);
            $this->redirect('/parents/create');
            return;
        }

        try {
            $parent = $this->service->create($userId, $schoolId, $_POST);
            $this->redirect('/parents/' . $parent['id'], 'Parent added.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect('/parents/create');
        }
    }

    /** GET /parents/{id} */
public function show(array $params): void
{
    $this->requirePermission('parents.view');

    $id = (int) ($params['id'] ?? 0);

    $parent = $this->service->getById($id);

    if (!$parent) {
        $this->redirect('/parents', 'Parent not found.', 'error');
        return;
    }

    $schoolId = RequestContext::schoolId();

    $this->view('parents/show', [
        'title' => trim(
            ($parent['first_name'] ?? '') . ' ' .
            ($parent['last_name'] ?? '')
        ),

        'parent'      => $parent,
        'children'    => $this->service->getLinkedStudents($id),

        // Only students not already linked to this parent
        'allStudents' => $schoolId
            ? $this->service->getUnlinkedStudents($schoolId, $id)
            : [],

        'errors' => Session::flash('errors') ?: [],
    ]);
}

    /** GET /parents/{id}/edit */
    public function edit(array $params): void
    {
        $this->requirePermission('parents.edit');
        $id = (int) ($params['id'] ?? 0);
        $parent = $this->service->getById($id);

        if (!$parent) {
            $this->redirect('/parents', 'Parent not found.', 'error');
            return;
        }

        $this->view('parents/edit', [
            'title'  => 'Edit Parent',
            'parent' => $parent,
        ]);
    }

    /** POST /parents/{id}/update */
    public function update(array $params): void
    {
        $this->requirePermission('parents.edit');
        $id = (int) ($params['id'] ?? 0);

        $this->service->update($id, [
            'phone'      => trim($_POST['phone'] ?? ''),
            'occupation' => trim($_POST['occupation'] ?? ''),
            'address'    => trim($_POST['address'] ?? ''),
        ]);

        $this->redirect("/parents/{$id}", 'Parent updated.');
    }

    /** POST /parents/link-student — used by BOTH the student page's "Link
     *  Parent" modal and the parent page's "Link Child" form. */
    public function linkStudent(array $params): void
    {
        $schoolId     = RequestContext::schoolId();
        $parentId     = (int) ($_POST['parent_id'] ?? 0);
        $studentId    = (int) ($_POST['student_id'] ?? 0);
        $relationship = $_POST['relationship'] ?? 'parent';

        // Where to send the admin back to depends on which page they came
        // from — the student page passes student_id first, the parent page
        // knows it's linking FROM a parent's own show page.
        $returnTo = !empty($_POST['from_parent']) ? "/parents/{$parentId}" : "/students/{$studentId}";

        try {
            $this->service->linkStudent($parentId, $studentId, $relationship, $schoolId);
            $this->redirect($returnTo, 'Linked successfully.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect($returnTo);
        }
    }

    /** POST /parents/{id}/unlink */
    public function unlinkStudent(array $params): void
    {
        $this->requirePermission('parents.edit');
        $schoolId  = RequestContext::schoolId();
        $parentId  = (int) ($params['id'] ?? 0);
        $studentId = (int) ($_POST['student_id'] ?? 0);

        try {
            $this->service->unlinkStudent($parentId, $studentId, $schoolId);
            $this->redirect("/parents/{$parentId}", 'Child unlinked.');
        } catch (\Throwable $e) {
            Session::flash('error', $e->getMessage());
            $this->redirect("/parents/{$parentId}");
        }
    }

    /**
     * GET /profile — a parent's own self-service page. Method named
     * `profile` (not `me`, matching the API controller) because the real
     * routes/web.php already registers [ParentController::class, 'profile']
     * for this path — swapping in this Web controller class only needs the
     * `use` import changed, not the route registration itself.
     */
    public function profile(array $params): void
    {
        $userId = RequestContext::userId();
        $parent = $this->service->getOwnRecord($userId);

        if (!$parent) {
            $this->view('errors/404', ['title' => 'Not Found', 'message' => 'No parent record is linked to your account.']);
            return;
        }

        $this->view('parents/profile', [
            'title'    => 'My Profile',
            'parent'   => $parent,
            'children' => $this->service->getLinkedStudents((int) $parent['id']),
            'errors'   => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /profile */
    public function updateProfile(array $params): void
    {
        $userId = RequestContext::userId();
        $parent = $this->service->getOwnRecord($userId);

        if (!$parent) {
            $this->redirect('/dashboard', 'No parent record linked to your account.', 'error');
            return;
        }

        $this->service->update((int) $parent['id'], [
            'phone'      => trim($_POST['phone'] ?? ''),
            'occupation' => trim($_POST['occupation'] ?? ''),
            'address'    => trim($_POST['address'] ?? ''),
        ]);

        $this->redirect('/profile', 'Profile updated.');
    }
}
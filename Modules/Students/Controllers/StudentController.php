<?php
declare(strict_types=1);

namespace Modules\Students\Controllers;

use Core\RequestContext;
use Core\Response;
use Core\Ownership;
use Modules\Students\Services\StudentService;

final class StudentController
{
    private StudentService $service;

    public function __construct(StudentService $service)
    {
        $this->service = $service;
    }

    public function create(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            Response::forbidden('no school context — cannot create student');
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $body['school_id'] = $schoolId;

        if (empty($body['admission_no']) || empty($body['first_name']) || empty($body['last_name']) || empty($body['class_id'])) {
            Response::badRequest('admission_no, first_name, last_name, and class_id are required.');
            return;
        }

        try {
            $student = $this->service->create($body);
            Response::created($student, 'student enrolled');
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function index(array $params): void
    {
        $roles    = RequestContext::roles();
        $userId   = RequestContext::userId();
        $schoolId = RequestContext::schoolId();

        if (in_array('parent', $roles, true)) {
            Response::success($this->service->listByParentUser($userId));
            return;
        }
        if (in_array('teacher', $roles, true)) {
            Response::success($this->service->listByTeacherUser($userId));
            return;
        }

        if (in_array('admin', $roles, true) || in_array('super_admin', $roles, true)) {
           Response::success($this->service->listByAdmin($schoolId, (int)($_GET['page'] ?? 1), (int)($_GET['per_page'] ?? 50)));
            return;
        }

        if ($schoolId === null) {
            Response::success([]);
            return;
        }

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, (int)($_GET['per_page'] ?? 50));

        $result     = $this->service->list($schoolId, $page, $perPage);
        $totalPages = (int)ceil($result['total'] / $perPage);

        Response::paginated($result['list'], [
            'page' => $page, 'per_page' => $perPage,
            'total' => $result['total'], 'total_pages' => $totalPages,
        ]);
    }

    public function search(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            Response::success([]);
            return;
        }
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 2) {
            Response::badRequest('search query must be at least 2 characters');
            return;
        }
        Response::success($this->service->search($schoolId, $q));
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            Response::badRequest('invalid student id');
            return;
        }

        $student = $this->service->getById($id);
        if (!$student) {
            Response::notFound('student not found');
            return;
        }

        // Same ownership gate the Go ExamHandler/FinanceHandler enforce —
        // a parent or teacher must be explicitly linked to this student.
        if (!Ownership::canAccessStudent($id)) {
            Response::forbidden('you do not have access to this student');
            return;
        }

        Response::success($student);
    }

    public function listByClass(array $params): void
    {
        $classId = (int)($params['classId'] ?? 0);
        if ($classId === 0) {
            Response::badRequest('invalid class id');
            return;
        }
        Response::success($this->service->listByClass($classId));
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            Response::badRequest('invalid student id');
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($body['first_name']) || empty($body['last_name'])) {
            Response::badRequest('first_name and last_name are required.');
            return;
        }

        try {
            $this->service->update($id, $body);
            Response::success(null, 'student updated');
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function destroy(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            Response::badRequest('invalid student id');
            return;
        }
        $this->service->deactivate($id, RequestContext::userId());
        Response::success(null, 'student deactivated');
    }

    public function parents(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            Response::badRequest('invalid student id');
            return;
        }
        Response::success($this->service->getParents($id));
    }
}

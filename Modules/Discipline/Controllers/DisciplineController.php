<?php
declare(strict_types=1);

namespace Modules\Discipline\Controllers;

use Core\Ownership;
use Core\RequestContext;
use Core\Response;
use Modules\Discipline\Repositories\DisciplineRepository;
use Modules\Discipline\Services\DisciplineService;

final class DisciplineController
{
    private DisciplineService $service;

    public function __construct()
    {
        $this->service = new DisciplineService(new DisciplineRepository());
    }

    public function create(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $d = $this->service->create($body, $schoolId, RequestContext::userId());
            Response::created($d, 'record created');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function index(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::success([]); return; }
        $termId = (int)($_GET['term_id'] ?? 0);
        Response::success($this->service->listBySchool($schoolId, $termId));
    }

    public function listByStudent(array $params): void
    {
        $studentId = (int)($params['studentId'] ?? 0);

        if (!Ownership::canAccessStudent($studentId)) {
            Response::forbidden('you do not have access to this student'); return;
        }

        Response::success($this->service->listByStudent($studentId));
    }

    public function destroy(array $params): void
    {
        try {
            $this->service->delete((int)($params['id'] ?? 0));
            Response::success(null, 'record deleted');
        } catch (\Throwable $e) { Response::serverError($e); }
    }
}

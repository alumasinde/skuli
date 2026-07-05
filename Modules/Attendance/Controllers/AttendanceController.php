<?php
declare(strict_types=1);

namespace Modules\Attendance\Controllers;

use Core\Ownership;
use Core\RequestContext;
use Core\Response;
use Modules\Attendance\Repositories\AttendanceRepository;
use Modules\Attendance\Services\AttendanceService;

final class AttendanceController
{
    private AttendanceService $service;

    public function __construct()
    {
        $this->service = new AttendanceService(new AttendanceRepository());
    }

    public function mark(array $params): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $this->service->mark($body, RequestContext::userId());
            Response::success(null, 'attendance recorded');
        } catch (\InvalidArgumentException $e) { Response::badRequest($e->getMessage()); }
        catch (\Throwable $e) { Response::serverError($e); }
    }

    public function getByClassDate(array $params): void
    {
        $classId = (int)($params['classId'] ?? 0);
        $date    = $_GET['date'] ?? '';
        try {
            Response::success($this->service->getByClassDate($classId, $date));
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function getByStudent(array $params): void
    {
        $studentId = (int)($params['studentId'] ?? 0);

        if (!Ownership::canAccessStudent($studentId)) {
            Response::forbidden('you do not have access to this student'); return;
        }

        $termId = (int)($_GET['term_id'] ?? 0);
        try {
            Response::success($this->service->getByStudent($studentId, $termId));
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function summary(array $params): void
    {
        $classId = (int)($params['classId'] ?? 0);
        $termId  = (int)($_GET['term_id'] ?? 0);
        try {
            Response::success($this->service->getSummary($classId, $termId));
        } catch (\Throwable $e) { Response::serverError($e); }
    }
}

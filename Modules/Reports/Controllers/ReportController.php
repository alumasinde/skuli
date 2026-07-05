<?php
declare(strict_types=1);

namespace Modules\Reports\Controllers;

use Core\Ownership;
use Core\RequestContext;
use Core\Response;
use Modules\Reports\Repositories\ReportRepository;
use Modules\Reports\Services\ReportService;

final class ReportController
{
    private ReportService $service;

    public function __construct()
    {
        $this->service = new ReportService(new ReportRepository());
    }

    public function reportCard(array $params): void
    {
        $studentId = (int)($params['studentId'] ?? 0);

        if (!Ownership::canAccessStudent($studentId)) {
            Response::forbidden('you do not have access to this student'); return;
        }

        $examId = (int)($_GET['exam_id'] ?? 0);
        if ($examId === 0) { Response::badRequest('exam_id is required.'); return; }

        $system = in_array($_GET['grade_system'] ?? '', ['kcse', 'cbc'], true)
            ? $_GET['grade_system'] : 'kcse';

        try {
            Response::success($this->service->getReportCard($studentId, $examId, $system));
        } catch (\InvalidArgumentException $e) { Response::notFound($e->getMessage()); }
        catch (\Throwable $e) { Response::serverError($e); }
    }

    public function updateRemarks(array $params): void
    {
        $schoolId  = RequestContext::schoolId();
        $studentId = (int)($params['studentId'] ?? 0);
        $examId    = (int)($_GET['exam_id'] ?? 0);

        if ($schoolId === null) { Response::forbidden('no school context'); return; }
        if ($examId === 0) { Response::badRequest('exam_id is required.'); return; }

        $body  = json_decode(file_get_contents('php://input'), true) ?? [];
        $roles = RequestContext::roles();

        $classTeacherId = $principalId = null;
        foreach ($roles as $role) {
            if ($role === 'teacher') $classTeacherId = RequestContext::userId();
            if ($role === 'admin')   $principalId    = RequestContext::userId();
        }

        try {
            $this->service->upsertRemarks(
                $schoolId, $studentId, $examId,
                !empty($body['class_teacher_remarks']) ? $body['class_teacher_remarks'] : null,
                $classTeacherId,
                !empty($body['principal_remarks']) ? $body['principal_remarks'] : null,
                $principalId
            );
            Response::success(null, 'remarks saved');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function classResults(array $params): void
    {
        $examId = (int)($_GET['exam_id'] ?? 0);
        if ($examId === 0) { Response::badRequest('exam_id is required.'); return; }
        Response::success($this->service->getClassResults($examId));
    }

    public function feeCollection(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::success([]); return; }
        $termId = (int)($_GET['term_id'] ?? 0);
        Response::success($this->service->getFeeCollection($schoolId, $termId));
    }

    public function attendanceSummary(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::success([]); return; }
        $termId = (int)($_GET['term_id'] ?? 0);
        Response::success($this->service->getAttendanceSummary($schoolId, $termId));
    }

    public function subjectPerformance(array $params): void
    {
        $examId = (int)($_GET['exam_id'] ?? 0);
        if ($examId === 0) { Response::badRequest('exam_id is required.'); return; }
        Response::success($this->service->getSubjectPerformance($examId));
    }
}

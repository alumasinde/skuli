<?php
declare(strict_types=1);

namespace Modules\Exams\Controllers;

use Core\Ownership;
use Core\RequestContext;
use Core\Response;
use Modules\Exams\Services\ExamService;
final class ExamController
{
    public function __construct(private ExamService $service) {}

    public function create(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $body['school_id'] = $schoolId;
        try {
            Response::created($this->service->createExam($body), 'exam created');
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function index(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($termId = (int) ($_GET['term_id'] ?? 0)) {
            Response::success($this->service->listByTerm($termId)); return;
        }
        if ($classId = (int) ($_GET['class_id'] ?? 0)) {
            Response::success($this->service->listByClass($classId)); return;
        }
        Response::success($schoolId ? $this->service->listExams($schoolId) : []);
    }

    public function show(array $params): void
    {
        $exam = $this->service->getExam((int) ($params['id'] ?? 0));
        $exam ? Response::success($exam) : Response::notFound('exam not found');
    }

    public function submitResults(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $this->service->submitResults($body, RequestContext::userId(), $schoolId);
            Response::success(null, 'results submitted');
        } catch (\InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function getResults(array $params): void
    {
        $examId  = (int) ($params['id'] ?? 0);
        $classId = (int) ($_GET['class_id'] ?? 0);
        try {
            $list = $classId
                ? $this->service->getResultsByExamAndClass($examId, $classId)
                : $this->service->getResultsByExamEnriched($examId);
            Response::success($list);
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function getStudentResults(array $params): void
    {
        $studentId = (int) ($params['studentId'] ?? 0);
        if (!Ownership::canAccessStudent($studentId)) {
            Response::forbidden('you do not have access to this student'); return;
        }
        $examId = (int) ($_GET['exam_id'] ?? 0);
        try {
            Response::success($this->service->getStudentResults($studentId, $examId));
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    /**
     * POST /exams/{id}/publish — admin-only action.
     * NOTE: this route must be registered behind PermissionMiddleware for the
     * 'exams.publish' permission (part of the access-control wiring already
     * underway on this project) — a controller-level check alone is not
     * sufficient defense in depth.
     */
    public function publish(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $examId = (int) ($params['id'] ?? 0);
        try {
            $summaries = $this->service->publishExam($examId, RequestContext::userId(), $schoolId);
            Response::success($summaries, 'exam published');
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    /** GET /exams/{id}/summaries — published results for report cards/dashboards. */
    public function getSummaries(array $params): void
    {
        $examId = (int) ($params['id'] ?? 0);
        try {
            Response::success($this->service->getExamSummaries($examId));
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    /** GET /students/{studentId}/exams/{id}/summary — single student's published report card row. */
    public function getStudentSummary(array $params): void
    {
        $studentId = (int) ($params['studentId'] ?? 0);
        if (!Ownership::canAccessStudent($studentId)) {
            Response::forbidden('you do not have access to this student'); return;
        }
        $examId = (int) ($params['id'] ?? ($_GET['exam_id'] ?? 0));
        try {
            $summary = $this->service->getStudentSummary($studentId, $examId);
            $summary ? Response::success($summary) : Response::notFound('summary not found');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function getGradeScales(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        Response::success($schoolId ? $this->service->getGradeScales($schoolId) : []);
    }

    public function createGradeScale(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $body['school_id'] = $schoolId;
        try {
            Response::created($this->service->createGradeScale($body), 'grade scale created');
        } catch (\InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function updateGradeScale(array $params): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $this->service->updateGradeScale((int) ($params['id'] ?? 0), $body);
            Response::success(null, 'grade scale updated');
        } catch (\InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function deleteGradeScale(array $params): void
    {
        try {
            $this->service->deleteGradeScale((int) ($params['id'] ?? 0));
            Response::success(null, 'grade scale deleted');
        } catch (\InvalidArgumentException $e) {
            Response::notFound($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }
}
<?php
declare(strict_types=1);

namespace Modules\Reports\Controllers\Web;

use Core\Ownership;
use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Exams\Services\ExamService;
use Modules\Reports\Services\ReportService;
use Modules\Students\Services\StudentService;
use Modules\Terms\Services\TermService;

final class ReportsWebController extends WebController
{
    public function __construct(
        private ReportService $service,
        private ExamService $exams,
        private StudentService $students,
        private TermService $terms
    ) {
        parent::__construct();
    }
    public function reportCard(array $params): void
    {
        $studentId = (int) ($params['studentId'] ?? 0);

        if (!Ownership::canAccessStudent($studentId)) {
            $this->redirect('/dashboard', 'You do not have access to this student.', 'error');
            return;
        }

        $student = $this->students->getById($studentId);
        if (!$student) {
            $this->redirect('/students', 'Student not found.', 'error');
            return;
        }

        $classId = (int) ($student['class_id'] ?? 0);
        $examsForClass = $classId ? $this->exams->listByClass($classId) : [];

        $examId = (int) ($_GET['exam_id'] ?? 0);
        if ($examId === 0 && !empty($examsForClass)) {
            // Default to the most recent exam — listByClass() orders by
            // start_date DESC per the Exams module, so [0] is the latest.
            $examId = (int) $examsForClass[0]['id'];
        }

        $grade_system = in_array($_GET['grade_system'] ?? '', ['kcse', 'cbc'], true) ? $_GET['grade_system'] : 'kcse';

        $card = null;
        if ($examId > 0) {
            try {
                $card = $this->service->getReportCard($studentId, $examId, $grade_system);
            } catch (\InvalidArgumentException $e) {
                Session::flash('errors', [$e->getMessage()]);
            }
        }

        $this->view('reports/report_card', [
            'title'         => 'Report Card',
            'student'       => $student,
            'card'          => $card,
            'exams'         => $examsForClass,
            'examId'        => $examId,
            'grade_system'  => $grade_system,
            'canEditRemarks'=> Session::hasRole('teacher') || Session::hasRole('admin'),
            'errors'        => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /reports/report-card/{studentId}/remarks */
    public function updateRemarks(array $params): void
    {
        $studentId = (int) ($params['studentId'] ?? 0);
        $examId    = (int) ($_POST['exam_id'] ?? 0);
        $schoolId  = RequestContext::schoolId();

        if (!Ownership::canAccessStudent($studentId) || $schoolId === null) {
            $this->redirect('/dashboard', 'You do not have access to this student.', 'error');
            return;
        }

        $roles = RequestContext::roles();
        $classTeacherId = in_array('teacher', $roles, true) ? RequestContext::userId() : null;
        $principalId    = in_array('admin', $roles, true)   ? RequestContext::userId() : null;

        try {
            $this->service->upsertRemarks(
                $schoolId, $studentId, $examId,
                trim($_POST['class_teacher_remarks'] ?? '') ?: null, $classTeacherId,
                trim($_POST['principal_remarks'] ?? '') ?: null, $principalId
            );
            $this->redirect("/reports/report-card/{$studentId}?exam_id={$examId}", 'Remarks saved.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/reports/report-card/{$studentId}?exam_id={$examId}");
        }
    }

    /** GET /reports/fee-collection — admin-only school-wide summary. */
    public function feeCollection(array $params): void
    {
        $this->requirePermission('finance.view');
        $schoolId = RequestContext::schoolId();

        $termId = (int) ($_GET['term_id'] ?? 0);
        $terms  = $schoolId ? $this->terms->listBySchool($schoolId) : [];

        if ($termId === 0) {
            foreach ($terms as $t) {
                if ($t['is_current'] ?? false) { $termId = (int) $t['id']; break; }
            }
        }

        $report = ($schoolId && $termId) ? $this->service->getFeeCollection($schoolId, $termId) : null;

        $this->view('reports/fee_collection', [
            'title'  => 'Fee Collection Report',
            'terms'  => $terms,
            'termId' => $termId,
            'report' => $report,
        ]);
    }
}
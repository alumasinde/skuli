<?php
declare(strict_types=1);

namespace Modules\Exams\Controllers\Web;

use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Classes\Services\ClassService;
use Modules\Exams\Services\ExamService;
use Modules\Students\Services\StudentService;
use Modules\Terms\Services\TermService;

final class ExamWebController extends WebController
{
    public function __construct(
        private ExamService    $service,
        private TermService    $terms,
        private ClassService   $classes,
        private StudentService $students
    ) {
        parent::__construct();
    }

    /** GET /exams */
    public function index(array $params): void
    {
        $schoolId = RequestContext::schoolId();

        $this->view('exams/index', [
            'title' => 'Exams',
            'exams' => $schoolId === null ? [] : $this->service->listExams($schoolId),
        ]);
    }

    /** GET /exams/create */
    public function create(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/exams', 'No school context.', 'error');
            return;
        }

        $this->view('exams/create', [
            'title'   => 'Create Exam',
            'terms'   => $this->terms->listBySchool($schoolId),
            'classes' => $this->classes->list($schoolId),
            'errors'  => Session::flash('errors') ?: [],
            'old'     => Session::flash('old') ?: [],
        ]);
    }

    /** POST /exams */
    public function store(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/exams', 'No school context.', 'error');
            return;
        }

        $body = [
            'school_id'  => $schoolId,
            'term_id'    => (int) ($_POST['term_id'] ?? 0),
            'class_id'   => (int) ($_POST['class_id'] ?? 0),
            'name'       => trim($_POST['name'] ?? ''),
            'type'       => $_POST['type'] ?? 'endterm',
            'start_date' => $_POST['start_date'] ?? '',
            'end_date'   => $_POST['end_date'] ?? '',
        ];

        try {
            $exam = $this->service->createExam($body);
            $this->redirect('/exams/' . $exam['id'], 'Exam created.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/exams/create');
        }
    }

    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        $exam = $id ? $this->service->getExam($id) : null;
        if (!$exam) {
            $this->redirect('/exams', 'Exam not found.', 'error');
            return;
        }

        $schoolId = RequestContext::schoolId();

        $classId = (int) ($_GET['class_id'] ?? $exam['class_id'] ?? 0);

        $marksheet = null;
        if ($classId > 0) {
            $students = $this->students->listByClass($classId);
            $subjects = $this->classes->getSubjects($classId);

            // Index existing results by "student_id:subject_id" for pre-filling.
            $existing = [];
            foreach ($this->service->getResultsByExamAndClass($id, $classId) as $r) {
                $existing[$r['student_id'] . ':' . $r['subject_id']] = $r;
            }

            $marksheet = [
                'class_id' => $classId,
                'students' => $students,
                'subjects' => $subjects,
                'existing' => $existing,
            ];
        }

        $this->view('exams/show', [
            'title'      => $exam['name'] ?? 'Exam',
            'exam'       => $exam,
            'classes'    => $schoolId ? $this->classes->list($schoolId) : [],
            'gradeScales'=> $schoolId ? $this->service->getGradeScales($schoolId) : [],
            'marksheet'  => $marksheet,
            'errors'     => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /exams/{id}/results — submit the marksheet grid. */
    public function submitResults(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/exams', 'No school context.', 'error');
            return;
        }

        $examId  = (int) ($params['id'] ?? 0);
        $classId = (int) ($_POST['class_id'] ?? 0);

        // $_POST['marks'][student_id][subject_id] = value
        // $_POST['remarks'][student_id][subject_id] = value
        $results = [];
        $maxMarks = (float) ($_POST['max_marks'] ?? 100);
        foreach (($_POST['marks'] ?? []) as $studentId => $bySubject) {
            foreach ((array) $bySubject as $subjectId => $mark) {
                $results[] = [
                    'student_id' => (int) $studentId,
                    'subject_id' => (int) $subjectId,
                    'marks'      => $mark,
                    'max_marks'  => $maxMarks,
                    'remarks'    => $_POST['remarks'][$studentId][$subjectId] ?? '',
                ];
            }
        }

        $payload = [
            'exam_id' => $examId,
            'class_id'=> $classId,
            'grade_system'  => $_POST['grade_system'] ?? 'kcse',
            'results' => $results,
        ];

        try {
            $this->service->submitResults($payload, RequestContext::userId(), $schoolId);
            $this->redirect("/exams/{$examId}?class_id={$classId}", 'Results saved.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/exams/{$examId}?class_id={$classId}");
        }
    }

    /**
     * POST /exams/{id}/publish — admin clicks "Publish Exam".
     * NOTE: register this route behind PermissionMiddleware for the
     * 'exams.publish' permission, same as the rest of the access-control
     * patch you're rolling out — this is deliberately not the only gate.
     */
    public function publish(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/exams', 'No school context.', 'error');
            return;
        }

        $examId = (int) ($params['id'] ?? 0);
        try {
            $this->service->publishExam($examId, RequestContext::userId(), $schoolId);
            $this->redirect("/exams/{$examId}/results", 'Exam published. Report cards are now available.');
        } catch (\Throwable $e) {
            $this->redirect("/exams/{$examId}", $e->getMessage(), 'error');
        }
    }

    /**
     * GET /exams/{id}/results — published class results / report-card table.
     * Always reads exam_student_summaries, never exam_results directly.
     */
    public function results(array $params): void
    {
        $examId = (int) ($params['id'] ?? 0);
        $exam   = $examId ? $this->service->getExam($examId) : null;
        if (!$exam) {
            $this->redirect('/exams', 'Exam not found.', 'error');
            return;
        }

        $this->view('exams/results', [
            'title'     => ($exam['name'] ?? 'Exam') . ' — Results',
            'exam'      => $exam,
            'published' => !empty($exam['published_at']),
            'summaries' => $this->service->getExamSummaries($examId),
        ]);
    }

    /** POST /exams/{id}/delete */
    public function destroy(array $params): void
    {
        $this->service->deleteExam((int) ($params['id'] ?? 0));
        $this->redirect('/exams', 'Exam deleted.');
    }

    // ── Grade Scales ──────────────────────────────────────────────────────────

    /** GET /exams/grade-scales */
    public function gradeScales(array $params): void
    {
        $schoolId = RequestContext::schoolId();

        $this->view('exams/grade_scales', [
            'title'  => 'Grade Scales',
            'scales' => $schoolId ? $this->service->getGradeScales($schoolId) : [],
            'errors' => Session::flash('errors') ?: [],
            'old'    => Session::flash('old') ?: [],
        ]);
    }

    /** POST /exams/grade-scales */
    public function storeGradeScale(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/exams/grade-scales', 'No school context.', 'error');
            return;
        }

        $body = [
            'school_id' => $schoolId,
            'grade_system'    => $_POST['grade_system'] ?? 'kcse',
            'grade'     => trim($_POST['grade'] ?? ''),
            'min_score' => $_POST['min_score'] ?? '',
            'max_score' => $_POST['max_score'] ?? '',
            'points'    => $_POST['points'] !== '' ? $_POST['points'] : null,
            'remark'    => trim($_POST['remark'] ?? ''),
        ];

        try {
            $this->service->createGradeScale($body);
            $this->redirect('/exams/grade-scales', 'Grade scale added.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/exams/grade-scales');
        }
    }

    /** POST /exams/grade-scales/{id}/delete */
    public function deleteGradeScale(array $params): void
    {
        try {
            $this->service->deleteGradeScale((int) ($params['id'] ?? 0));
            $this->redirect('/exams/grade-scales', 'Grade scale deleted.');
        } catch (\Throwable $e) {
            $this->redirect('/exams/grade-scales', $e->getMessage(), 'error');
        }
    }
}
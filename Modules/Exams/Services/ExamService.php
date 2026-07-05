<?php
declare(strict_types=1);

namespace Modules\Exams\Services;

use Modules\Exams\Repositories\ExamRepository;

final class ExamService
{
    public function __construct(private readonly ExamRepository $repo) {}

    public function createExam(array $data): array
    {
        $schoolId = (int) ($data['school_id'] ?? 0);
        $termId   = (int) ($data['term_id'] ?? 0);
        $classId  = (int) ($data['class_id'] ?? 0);

        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new \InvalidArgumentException('Exam name is required.');
        }
        if ($termId === 0) {
            throw new \InvalidArgumentException('A term must be selected.');
        }
        if (empty($data['start_date']) || empty($data['end_date'])) {
            throw new \InvalidArgumentException('Start and end dates are required.');
        }
        if (strtotime((string) $data['end_date']) < strtotime((string) $data['start_date'])) {
            throw new \InvalidArgumentException('End date cannot be before the start date.');
        }
        if (!$this->repo->termBelongsToSchool($termId, $schoolId)) {
            throw new \RuntimeException('Selected term is invalid.');
        }
        if ($classId > 0 && !$this->repo->classBelongsToSchool($classId, $schoolId)) {
            throw new \RuntimeException('Selected class is invalid.');
        }

        $data['class_id'] = $classId > 0 ? $classId : null;
        $id = $this->repo->create($data);

        return $this->repo->findById($id);
    }

    public function listExams(int $schoolId): array   { return $this->repo->listBySchool($schoolId); }
    public function listByTerm(int $termId): array     { return $this->repo->listByTerm($termId); }
    public function listByClass(int $classId): array   { return $this->repo->listByClass($classId); }
    public function getExam(int $id): ?array           { return $this->repo->findById($id); }
    public function deleteExam(int $id): void          { $this->repo->softDeleteExam($id); }
    public function getGradeScales(int $schoolId): array { return $this->repo->getGradeScales($schoolId); }

    public function createGradeScale(array $data): array
    {
        $this->validateGradeScale($data);
        $id = $this->repo->createGradeScale($data);
        return $this->repo->findGradeScaleById($id);
    }

    public function updateGradeScale(int $id, array $data): void
    {
        if (!$this->repo->findGradeScaleById($id)) {
            throw new \InvalidArgumentException('Grade scale not found.');
        }
        $this->validateGradeScale($data);
        $this->repo->updateGradeScale($id, $data);
    }

    public function deleteGradeScale(int $id): void
    {
        if (!$this->repo->findGradeScaleById($id)) {
            throw new \InvalidArgumentException('Grade scale not found.');
        }
        $this->repo->deleteGradeScale($id);
    }

    /**
     * Submit a marksheet. $data:
     *   exam_id, class_id, system (kcse|cbc), results[] of {student_id, subject_id, marks, max_marks?, remarks?}
     */
    public function submitResults(array $data, int $gradedBy, int $schoolId): void
    {
        $examId  = (int) ($data['exam_id'] ?? 0);
        $classId = (int) ($data['class_id'] ?? 0);
        if ($examId === 0) {
            throw new \InvalidArgumentException('Missing exam.');
        }
        if ($classId === 0) {
            throw new \InvalidArgumentException('Please select a class before submitting.');
        }

        $exam = $this->repo->findById($examId);
        if (!$exam || (int) $exam['school_id'] !== $schoolId) {
            throw new \InvalidArgumentException('Exam not found.');
        }

        $system = in_array($data['grade_system'] ?? 'kcse', ['kcse', 'cbc'], true) ? $data['grade_system'] : 'kcse';
        $scales = $this->repo->loadGradeScales($schoolId, $system);
        $rows   = $data['results'] ?? [];

        $records = [];
        foreach ($rows as $row) {
            if (!isset($row['student_id'], $row['subject_id'])) {
                continue;
            }
            // Skip blank cells (no marks entered for that student/subject).
            if (!isset($row['marks']) || $row['marks'] === '' || $row['marks'] === null) {
                continue;
            }

            $marks    = (float) $row['marks'];
            $maxMarks = (float) ($row['max_marks'] ?? 100);
            if ($maxMarks <= 0) {
                throw new \InvalidArgumentException('Maximum marks must be greater than zero.');
            }
            if ($marks < 0 || $marks > $maxMarks) {
                throw new \InvalidArgumentException("Marks must be between 0 and {$maxMarks}.");
            }

            $records[] = [
                'exam_id'    => $examId,
                'student_id' => (int) $row['student_id'],
                'subject_id' => (int) $row['subject_id'],
                'class_id'   => $classId,
                'graded_by'  => $gradedBy,
                'marks'      => $marks,
                'max_marks'  => $maxMarks,
                'remarks'    => trim((string) ($row['remarks'] ?? '')),
            ];
        }

        if (empty($records)) {
            throw new \InvalidArgumentException('No marks were entered.');
        }

        $this->repo->bulkUpsertResults($records, $scales);
    }

    public function getStudentResults(int $studentId, int $examId = 0): array
    {
        return $this->repo->getStudentResults($studentId, $examId);
    }

    public function getResultsByExamEnriched(int $examId): array
    {
        return $this->repo->getResultsByExamEnriched($examId);
    }

    public function getResultsByExamAndClass(int $examId, int $classId): array
    {
        return $this->repo->getResultsByExamAndClass($examId, $classId);
    }
    public function publishExam(int $examId, int $userId, int $schoolId, string $grade_system = 'kcse'): array
    {
        $exam = $this->repo->findById($examId);
        if (!$exam || (int) $exam['school_id'] !== $schoolId) {
            throw new \InvalidArgumentException('Exam not found.');
        }
        if (!empty($exam['locked_at'])) {
            throw new \RuntimeException('This exam is locked and can no longer be republished.');
        }

        $aggregates = $this->repo->aggregateResultsForExam($examId);
        if (empty($aggregates)) {
            throw new \InvalidArgumentException('No results have been entered for this exam yet.');
        }

        $scales = $this->repo->loadGradeScales($schoolId, $grade_system);
        $levels = $this->repo->loadPerformanceLevels($schoolId, $grade_system);

        // Group by class first, since positions are computed per class.
        $byClass = [];
        foreach ($aggregates as $row) {
            $average = (float) $row['average'];
            $row['grade']   = $this->repo->resolveOverallGrade($scales, $average);
            $row['remarks'] = $this->repo->resolvePerformanceRemark($levels, $average);
            $byClass[(int) $row['class_id']][] = $row;
        }

        $summaries = [];
        foreach ($byClass as $rows) {
            usort($rows, static fn($a, $b) => $b['average'] <=> $a['average']);

            // Competition ranking: tied averages share a position, and the
            // next distinct average skips ahead accordingly (1, 1, 3, 4 ...).
            $position = 0;
            $rank     = 0;
            $lastAvg  = null;
            foreach ($rows as $row) {
                $rank++;
                if ($lastAvg === null || (float) $row['average'] < $lastAvg) {
                    $position = $rank;
                    $lastAvg  = (float) $row['average'];
                }
                $row['position'] = $position;
                $summaries[] = $row;
            }
        }

        $this->repo->replaceSummaries($examId, $summaries, $userId);
        $this->repo->markPublished($examId, $userId);

        return $this->repo->getExamSummaries($examId);
    }

    public function getExamSummaries(int $examId): array
    {
        return $this->repo->getExamSummaries($examId);
    }

    public function getStudentSummary(int $studentId, int $examId): ?array
    {
        return $this->repo->getStudentSummary($studentId, $examId);
    }

    public function getSummariesForStudent(int $studentId): array
    {
        return $this->repo->getSummariesForStudent($studentId);
    }

    private function validateGradeScale(array $d): void
    {
        if (trim((string) ($d['grade'] ?? '')) === '') {
            throw new \InvalidArgumentException('Grade label is required.');
        }
        $min = (float) ($d['min_score'] ?? -1);
        $max = (float) ($d['max_score'] ?? -1);
        if ($min < 0 || $max < 0) {
            throw new \InvalidArgumentException('Min and max scores are required.');
        }
        if ($max < $min) {
            throw new \InvalidArgumentException('Max score cannot be below min score.');
        }
    }
}
<?php
declare(strict_types=1);

namespace Modules\Reports\Services;

use Modules\Reports\Repositories\ReportRepository;

final class ReportService
{
    public function __construct(private readonly ReportRepository $repo)
    {
    }

    /** Full report card for one student on one exam. */
    public function getReportCard(int $studentId, int $examId, string $system = 'kcse'): array
    {
        $student = $this->repo->getStudentInfo($studentId);
        if (!$student) {
            throw new \InvalidArgumentException('Student not found.');
        }

        $examInfo = $this->repo->getExamWithTerm($examId);
        $scales   = $this->repo->getGradeScales((int)$student['school_id'], $system);
        $levels   = $this->repo->getPerformanceLevels((int)$student['school_id'], $system);
        $rows     = $this->repo->getSubjectResults($studentId, $examId);

        $results     = [];
        $totalMarks  = 0.0;
        $totalMax    = 0.0;
        $totalPoints = null;

        foreach ($rows as $r) {
            $pct  = (float)$r['max_marks'] > 0 ? (float)$r['marks'] / (float)$r['max_marks'] * 100 : 0.0;
            $grade  = $this->lookupGrade($scales, $pct);
            $points = $this->lookupPoints($scales, $pct);
            $level  = $this->lookupLevel($levels, $pct);

            $rank      = $this->repo->getSubjectRank($examId, (int)$r['subject_id'], $pct);
            $classSize = $this->repo->getSubjectClassSize($examId, (int)$r['subject_id']);

            $results[] = [
                'subject_id'        => (int)$r['subject_id'],
                'subject_name'      => $r['subject_name'],
                'subject_code'      => $r['subject_code'],
                'marks'             => (float)$r['marks'],
                'max_marks'         => (float)$r['max_marks'],
                'percentage'        => round($pct, 2),
                'grade'             => $grade,
                'points'            => $points,
                'rank'              => $rank,
                'class_size_subject'=> $classSize,
                'remarks'           => $r['remarks'],
                'performance_level' => $level,
                'teacher_name'      => $r['teacher_name'],
            ];

            $totalMarks += (float)$r['marks'];
            $totalMax   += (float)$r['max_marks'];

            if ($system === 'kcse' && $points !== null) {
                $totalPoints = ($totalPoints ?? 0.0) + $points;
            }
        }

        $average      = $totalMax > 0 ? round($totalMarks / $totalMax * 100, 2) : 0.0;
        $overallGrade = $this->lookupGrade($scales, $average);
        $overallLevel = $this->lookupLevel($levels, $average);
        $meanPoints   = ($totalPoints !== null && count($results) > 0)
            ? round($totalPoints / count($results), 2)
            : null;

        $position  = $this->repo->getOverallPosition($examId, $studentId);
        $classSize = $this->repo->getClassSize($examId);
        $attPct    = $this->repo->getAttendancePct($studentId, $examId);
        $remarks   = $this->repo->getRemarks($studentId, $examId);

        return [
            'system'                  => $system,
            'school_id'               => (int)$student['school_id'],
            'student_id'              => $studentId,
            'student_name'            => $student['student_name'],
            'admission_no'            => $student['admission_no'],
            'class_name'              => $student['class_name'],
            'photo_url'               => $student['photo_url'] ?? '',
            'exam_id'                 => $examId,
            'exam_name'               => $examInfo['exam_name'] ?? '',
            'term_name'               => $examInfo['term_name'] ?? '',
            'term_start_date'         => $examInfo['term_start_date'] ?? null,
            'term_end_date'           => $examInfo['term_end_date'] ?? null,
            'results'                 => $results,
            'total_marks'             => $totalMarks,
            'total_max'               => $totalMax,
            'average'                 => $average,
            'overall_grade'           => $overallGrade,
            'overall_performance_level' => $overallLevel,
            'total_points'            => $totalPoints,
            'mean_points'             => $meanPoints,
            'position'                => $position,
            'class_size'              => $classSize,
            'attendance_pct'          => $attPct,
            'class_teacher_remarks'   => $remarks['class_teacher_remarks'] ?? '',
            'class_teacher_name'      => $remarks['class_teacher_name'] ?? '',
            'principal_remarks'       => $remarks['principal_remarks'] ?? '',
            'principal_name'          => $remarks['principal_name'] ?? '',
        ];
    }

    public function upsertRemarks(
        int $schoolId, int $studentId, int $examId,
        ?string $classTeacherRemarks, ?int $classTeacherId,
        ?string $principalRemarks, ?int $principalId
    ): void {
        $this->repo->upsertRemarks(
            $schoolId, $studentId, $examId,
            $classTeacherRemarks, $classTeacherId,
            $principalRemarks, $principalId
        );
    }

    public function getClassResults(int $examId): array
    {
        return $this->repo->getClassResults($examId);
    }

    public function getFeeCollection(int $schoolId, int $termId): array
    {
        $r = $this->repo->getFeeCollection($schoolId, $termId);
        $r['balance'] = (float)($r['total_billed'] ?? 0) - (float)($r['total_paid'] ?? 0);
        return $r;
    }

    public function getAttendanceSummary(int $schoolId, int $termId): array
    {
        return $this->repo->getAttendanceSummary($schoolId, $termId);
    }

    public function getSubjectPerformance(int $examId): array
    {
        return $this->repo->getSubjectPerformance($examId);
    }

    // ── Private grade-scale helpers ───────────────────────────────────────────

    private function lookupGrade(array $scales, float $pct): string
    {
        foreach ($scales as $s) {
            if ($pct >= (float)$s['min_score'] && $pct <= (float)$s['max_score']) {
                return $s['grade'];
            }
        }
        return '';
    }

    private function lookupPoints(array $scales, float $pct): ?float
    {
        foreach ($scales as $s) {
            if ($pct >= (float)$s['min_score'] && $pct <= (float)$s['max_score']) {
                return $s['points'] !== null ? (float)$s['points'] : null;
            }
        }
        return null;
    }

    private function lookupLevel(array $levels, float $pct): string
    {
        foreach ($levels as $l) {
            if ($pct >= (float)$l['min_score'] && $pct <= (float)$l['max_score']) {
                return $l['label'];
            }
        }
        return '';
    }
}

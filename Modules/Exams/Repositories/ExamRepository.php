<?php
declare(strict_types=1);

namespace Modules\Exams\Repositories;

use Core\Repository;

final class ExamRepository extends Repository
{
    // ── Exams ────────────────────────────────────────────────────────────────

    public function create(array $data): int
    {
        return $this->insert('
            INSERT INTO exams (school_id, term_id, class_id, name, type, start_date, end_date)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ', [
            $data['school_id'], $data['term_id'], $data['class_id'] ?? null,
            $data['name'], $data['type'] ?? 'endterm',
            $data['start_date'], $data['end_date'],
        ]);
    }

    public function listBySchool(int $schoolId): array
    {
        return $this->fetchAll('
            SELECT e.*, t.name AS term_name, c.name AS class_name,
                   (SELECT COUNT(DISTINCT er.student_id) FROM exam_results er
                     WHERE er.exam_id = e.id AND er.deleted_at IS NULL) AS graded_students
            FROM exams e
            JOIN terms t        ON t.id = e.term_id
            LEFT JOIN classes c ON c.id = e.class_id
            WHERE e.school_id = ? AND e.deleted_at IS NULL
            ORDER BY e.start_date DESC
        ', [$schoolId]);
    }

    public function listByTerm(int $termId): array
    {
        return $this->fetchAll(
            'SELECT * FROM exams WHERE term_id = ? AND deleted_at IS NULL ORDER BY start_date',
            [$termId]
        );
    }

    public function listByClass(int $classId): array
    {
        return $this->fetchAll(
            'SELECT * FROM exams WHERE class_id = ? AND deleted_at IS NULL ORDER BY start_date DESC',
            [$classId]
        );
    }

    public function findById(int $id): ?array
    {
        // deleted_at guard added; term/class names joined for the detail page.
        return $this->fetchOne('
            SELECT e.*, t.name AS term_name, c.name AS class_name
            FROM exams e
            JOIN terms t        ON t.id = e.term_id
            LEFT JOIN classes c ON c.id = e.class_id
            WHERE e.id = ? AND e.deleted_at IS NULL
        ', [$id]);
    }

    public function softDeleteExam(int $id): void
    {
        $this->execute('UPDATE exams SET deleted_at = NOW() WHERE id = ?', [$id]);
    }

    /** Stamps the exam as published. Called once summaries have been written. */
    public function markPublished(int $examId, int $userId): void
    {
        $this->execute(
            'UPDATE exams SET published_at = NOW(), published_by = ? WHERE id = ?',
            [$userId, $examId]
        );
    }

    /** Tenant safety: does this term belong to the school? */
    public function termBelongsToSchool(int $termId, int $schoolId): bool
    {
        return $this->fetchOne('
            SELECT t.id FROM terms t
            JOIN academic_years ay ON ay.id = t.academic_year_id
            WHERE t.id = ? AND ay.school_id = ? AND t.deleted_at IS NULL
        ', [$termId, $schoolId]) !== null;
    }

    public function classBelongsToSchool(int $classId, int $schoolId): bool
    {
        return $this->fetchOne(
            'SELECT id FROM classes WHERE id = ? AND school_id = ? AND deleted_at IS NULL',
            [$classId, $schoolId]
        ) !== null;
    }

    // ── Grade Scales ─────────────────────────────────────────────────────────

    public function loadGradeScales(int $schoolId, string $system = 'kcse'): array
    {
        return $this->fetchAll('
            SELECT id, school_id, grade_system, grade, min_score, max_score, points, remark
            FROM grade_scales
            WHERE school_id = ? AND grade_system = ? AND deleted_at IS NULL
            ORDER BY min_score DESC
        ', [$schoolId, $system]);
    }

    public function getGradeScales(int $schoolId): array
    {
        return $this->fetchAll('
            SELECT id, school_id, grade_system, grade, min_score, max_score, points, remark
            FROM grade_scales
            WHERE school_id = ? AND deleted_at IS NULL
            ORDER BY grade_system, min_score DESC
        ', [$schoolId]);
    }

    /** Overall-performance bands (e.g. "Outstanding, lead on!") keyed by average %. */
    public function loadPerformanceLevels(int $schoolId, string $system = 'kcse'): array
    {
        return $this->fetchAll('
            SELECT min_score, max_score, label
            FROM performance_levels
            WHERE school_id = ? AND grade_system = ? AND deleted_at IS NULL
            ORDER BY min_score DESC
        ', [$schoolId, $system]);
    }

    public function findGradeScaleById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM grade_scales WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public function createGradeScale(array $data): int
    {
        return $this->insert('
            INSERT INTO grade_scales (school_id, grade_system, grade, min_score, max_score, points, remark)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ', [
            $data['school_id'], $data['grade_system'] ?? 'kcse',
            $data['grade'], $data['min_score'], $data['max_score'],
            $data['points'] ?? null, $data['remark'] ?? null,
        ]);
    }

    public function updateGradeScale(int $id, array $data): void
    {
        $this->execute('
            UPDATE grade_scales SET grade = ?, min_score = ?, max_score = ?, points = ?, remark = ?
            WHERE id = ? AND deleted_at IS NULL
        ', [
            $data['grade'], $data['min_score'], $data['max_score'],
            $data['points'] ?? null, $data['remark'] ?? null, $id,
        ]);
    }

    /** Soft delete to match the rest of the system (was a hard DELETE). */
    public function deleteGradeScale(int $id): void
    {
        $this->execute('UPDATE grade_scales SET deleted_at = NOW() WHERE id = ?', [$id]);
    }

    // ── Exam Results ─────────────────────────────────────────────────────────
    private function resolveBand(array $bands, float $pct, string $field): ?string
    {
        foreach ($bands as $b) {
            if ($pct >= (float) $b['min_score'] && $pct <= (float) $b['max_score']) {
                return $b[$field] ?? null;
            }
        }
        return null; // no matching band — store NULL rather than a fake placeholder
    }

    public function bulkUpsertResults(array $records, array $scales): void
    {
        $pdo = $this->db;
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                INSERT INTO exam_results
                    (exam_id, student_id, subject_id, class_id, graded_by, marks, max_marks, grade, remarks)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    marks     = VALUES(marks),
                    max_marks = VALUES(max_marks),
                    grade     = VALUES(grade),
                    remarks   = VALUES(remarks),
                    graded_by = VALUES(graded_by),
                    deleted_at = NULL
            ');
            foreach ($records as $r) {
                $pct   = $r['max_marks'] > 0 ? ($r['marks'] / $r['max_marks'] * 100) : 0;
                $grade = $this->resolveBand($scales, $pct, 'grade');
                $stmt->execute([
                    $r['exam_id'], $r['student_id'], $r['subject_id'],
                    $r['class_id'], $r['graded_by'],
                    $r['marks'], $r['max_marks'], $grade,
                    ($r['remarks'] !== '' ? $r['remarks'] : null),
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function getStudentResults(int $studentId, int $examId = 0): array
    {
        $sql = '
            SELECT er.id, er.exam_id, er.student_id, er.subject_id, er.class_id,
                   er.graded_by, er.marks, er.max_marks, er.grade, er.remarks,
                   er.created_at, er.updated_at,
                   sub.name AS subject_name, sub.code AS subject_code,
                   e.name   AS exam_name,
                   CONCAT(st.first_name, \' \', st.last_name) AS student_name,
                   st.admission_no
            FROM exam_results er
            JOIN subjects sub ON sub.id = er.subject_id
            JOIN exams    e   ON e.id   = er.exam_id
            JOIN students st  ON st.id  = er.student_id
            WHERE er.student_id = ? AND er.deleted_at IS NULL';
        $params = [$studentId];

        if ($examId > 0) {
            $sql .= ' AND er.exam_id = ?';
            $params[] = $examId;
        }
        $sql .= ' ORDER BY e.start_date DESC, sub.name';

        return $this->fetchAll($sql, $params);
    }

    public function getResultsByExamEnriched(int $examId): array
    {
        return $this->fetchResultsByExam($examId);
    }

    public function getResultsByExamAndClass(int $examId, int $classId): array
    {
        return $this->fetchResultsByExam($examId, $classId);
    }

    /**
     * Shared query for the two lookups above — they differed only by an
     * optional class filter, so keeping one copy here means the join/column
     * list can't drift out of sync between them.
     */
    private function fetchResultsByExam(int $examId, ?int $classId = null): array
    {
        $sql = '
            SELECT er.id, er.exam_id, er.student_id, er.subject_id, er.class_id,
                   er.graded_by, er.marks, er.max_marks, er.grade, er.remarks,
                   CONCAT(s.first_name, \' \', s.last_name) AS student_name,
                   s.admission_no,
                   sub.name AS subject_name, sub.code AS subject_code
            FROM exam_results er
            JOIN students s   ON s.id   = er.student_id
            JOIN subjects sub ON sub.id = er.subject_id
            WHERE er.exam_id = ? AND er.deleted_at IS NULL';
        $params = [$examId];

        if ($classId !== null) {
            $sql .= ' AND er.class_id = ?';
            $params[] = $classId;
        }
        $sql .= ' ORDER BY s.last_name, s.first_name, sub.name';

        return $this->fetchAll($sql, $params);
    }

    // ── Exam Student Summaries (published results) ─────────────────────────────

    /**
     * One row per student who has at least one graded subject in this exam.
     * `average` is the mean of each subject's percentage (not a total-marks
     * weighted average), so a student's average isn't skewed by subjects
     * that happen to be marked out of a different max_marks.
     */
    public function aggregateResultsForExam(int $examId): array
    {
        return $this->fetchAll('
            SELECT student_id, class_id,
                   COUNT(*)                              AS subjects,
                   SUM(marks)                             AS total_marks,
                   SUM(max_marks)                         AS total_possible,
                   ROUND(AVG(marks / max_marks * 100), 2) AS average
            FROM exam_results
            WHERE exam_id = ? AND deleted_at IS NULL AND max_marks > 0
            GROUP BY student_id, class_id
        ', [$examId]);
    }

    public function resolveOverallGrade(array $scales, float $average): ?string
    {
        return $this->resolveBand($scales, $average, 'grade');
    }

    public function resolvePerformanceRemark(array $levels, float $average): ?string
    {
        return $this->resolveBand($levels, $average, 'label');
    }

    /**
     * Replaces the published summary rows for this exam in one transaction:
     * stale rows (students who no longer have any exam_results, e.g. after a
     * mark was deleted) are removed, then the current rows are upserted.
     * One row per (exam_id, student_id) — matches the uq_exam_student key.
     */
    public function replaceSummaries(int $examId, array $summaries, int $publishedBy): void
    {
        $pdo = $this->db;
        $pdo->beginTransaction();
        try {
            $keepIds = array_map(static fn($s) => (int) $s['student_id'], $summaries);

            if (empty($keepIds)) {
                $pdo->prepare('DELETE FROM exam_student_summaries WHERE exam_id = ?')
                    ->execute([$examId]);
            } else {
                $placeholders = implode(',', array_fill(0, count($keepIds), '?'));
                $pdo->prepare("
                    DELETE FROM exam_student_summaries
                    WHERE exam_id = ? AND student_id NOT IN ($placeholders)
                ")->execute([$examId, ...$keepIds]);
            }

            $stmt = $pdo->prepare('
                INSERT INTO exam_student_summaries
                    (exam_id, student_id, class_id, subjects, total_marks, total_possible,
                     average, grade, position, remarks, published_by, published_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    class_id       = VALUES(class_id),
                    subjects       = VALUES(subjects),
                    total_marks    = VALUES(total_marks),
                    total_possible = VALUES(total_possible),
                    average        = VALUES(average),
                    grade          = VALUES(grade),
                    position       = VALUES(position),
                    remarks        = VALUES(remarks),
                    published_by   = VALUES(published_by),
                    published_at   = NOW()
            ');
            foreach ($summaries as $s) {
                $stmt->execute([
                    $examId, $s['student_id'], $s['class_id'], $s['subjects'],
                    $s['total_marks'], $s['total_possible'], $s['average'],
                    $s['grade'], $s['position'], $s['remarks'], $publishedBy,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    /** Report-card / dashboard read path — always this table, never exam_results. */
    public function getExamSummaries(int $examId): array
    {
        return $this->fetchAll('
            SELECT ess.*,
                   CONCAT(s.first_name, \' \', s.last_name) AS student_name,
                   s.admission_no,
                   c.name AS class_name
            FROM exam_student_summaries ess
            JOIN students s ON s.id = ess.student_id
            JOIN classes  c ON c.id = ess.class_id
            WHERE ess.exam_id = ?
            ORDER BY c.name, ess.position IS NULL, ess.position, s.last_name, s.first_name
        ', [$examId]);
    }

    public function getStudentSummary(int $studentId, int $examId): ?array
    {
        return $this->fetchOne('
            SELECT ess.*, e.name AS exam_name, e.type AS exam_type
            FROM exam_student_summaries ess
            JOIN exams e ON e.id = ess.exam_id
            WHERE ess.student_id = ? AND ess.exam_id = ?
        ', [$studentId, $examId]);
    }

    /** All published exams for a student — dashboard/history view. */
    public function getSummariesForStudent(int $studentId): array
    {
        return $this->fetchAll('
            SELECT ess.*, e.name AS exam_name, e.type AS exam_type, e.start_date
            FROM exam_student_summaries ess
            JOIN exams e ON e.id = ess.exam_id
            WHERE ess.student_id = ? AND e.deleted_at IS NULL
            ORDER BY e.start_date DESC
        ', [$studentId]);
    }
}
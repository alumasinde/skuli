<?php
declare(strict_types=1);

namespace Modules\Reports\Repositories;

use Core\Repository;

final class ReportRepository extends Repository
{
    public function getStudentInfo(int $studentId): ?array
    {
        return $this->fetchOne('
            SELECT s.id, s.school_id, s.admission_no, s.photo_url,
                   TRIM(CONCAT(s.first_name, \' \',
                        IF(s.middle_name <> \'\', CONCAT(s.middle_name, \' \'), \'\'),
                        s.last_name))         AS student_name,
                   c.name                     AS class_name
            FROM students s
            JOIN classes c ON c.id = s.class_id
            WHERE s.id = ?
        ', [$studentId]);
    }

    public function getExamWithTerm(int $examId): ?array
    {
        return $this->fetchOne('
            SELECT e.name AS exam_name, t.name AS term_name,
                   t.start_date AS term_start_date, t.end_date AS term_end_date
            FROM exams e
            JOIN terms t ON t.id = e.term_id
            WHERE e.id = ?
        ', [$examId]);
    }

    public function getGradeScales(int $schoolId, string $grade_system): array
    {
        return $this->fetchAll('
            SELECT grade, min_score, max_score, points
            FROM grade_scales
            WHERE school_id = ? AND grade_system = ? AND deleted_at IS NULL
            ORDER BY min_score DESC
        ', [$schoolId, $grade_system]);
    }

    public function getPerformanceLevels(int $schoolId, string $grade_system): array
    {
        return $this->fetchAll('
            SELECT min_score, max_score, label
            FROM performance_levels
            WHERE school_id = ? AND grade_system = ? AND deleted_at IS NULL
            ORDER BY min_score DESC
        ', [$schoolId, $grade_system]);
    }

    public function getSubjectResults(int $studentId, int $examId): array
    {
        return $this->fetchAll('
            SELECT er.subject_id, sub.name AS subject_name, sub.code AS subject_code,
                   er.marks, er.max_marks, COALESCE(er.grade, \'\') AS grade,
                   er.remarks,
                   CONCAT(u.first_name, \' \', u.last_name) AS teacher_name
            FROM exam_results er
            JOIN subjects sub ON sub.id = er.subject_id
            JOIN users    u   ON u.id   = er.graded_by
            WHERE er.student_id = ? AND er.exam_id = ?
            ORDER BY sub.name
        ', [$studentId, $examId]);
    }

    public function getSubjectRank(int $examId, int $subjectId, float $pct): int
    {
        return (int)$this->fetchColumn('
            SELECT COUNT(*) + 1 FROM exam_results
            WHERE exam_id = ? AND subject_id = ?
              AND (marks / NULLIF(max_marks, 0)) > ?
        ', [$examId, $subjectId, $pct / 100]);
    }

    public function getSubjectClassSize(int $examId, int $subjectId): int
    {
        return (int)$this->fetchColumn(
            'SELECT COUNT(*) FROM exam_results WHERE exam_id = ? AND subject_id = ?',
            [$examId, $subjectId]
        );
    }

    public function getOverallPosition(int $examId, int $studentId): int
    {
        return (int)$this->fetchColumn('
            SELECT COUNT(*) + 1 FROM (
                SELECT student_id, SUM(marks) / SUM(max_marks) * 100 AS avg
                FROM exam_results WHERE exam_id = ?
                GROUP BY student_id
                HAVING avg > (
                    SELECT SUM(marks) / SUM(max_marks) * 100
                    FROM exam_results WHERE exam_id = ? AND student_id = ?
                )
            ) ranked
        ', [$examId, $examId, $studentId]);
    }

    public function getClassSize(int $examId): int
    {
        return (int)$this->fetchColumn(
            'SELECT COUNT(DISTINCT student_id) FROM exam_results WHERE exam_id = ?',
            [$examId]
        );
    }

    public function getAttendancePct(int $studentId, int $examId): float
    {
        return (float)$this->fetchColumn('
            SELECT COALESCE(ROUND(SUM(a.status = \'present\') / COUNT(*) * 100, 2), 0)
            FROM attendance a
            JOIN exams e ON e.term_id = a.term_id
            WHERE a.student_id = ? AND e.id = ?
        ', [$studentId, $examId]);
    }

    public function getRemarks(int $studentId, int $examId): ?array
    {
        return $this->fetchOne('
            SELECT rr.class_teacher_remarks, rr.principal_remarks,
                   CONCAT(ctu.first_name, \' \', ctu.last_name) AS class_teacher_name,
                   CONCAT(pu.first_name,  \' \', pu.last_name)  AS principal_name
            FROM report_remarks rr
            LEFT JOIN users ctu ON ctu.id = rr.class_teacher_id
            LEFT JOIN users pu  ON pu.id  = rr.principal_id
            WHERE rr.student_id = ? AND rr.exam_id = ?
        ', [$studentId, $examId]);
    }

    public function upsertRemarks(
        int $schoolId, int $studentId, int $examId,
        ?string $classTeacherRemarks, ?int $classTeacherId,
        ?string $principalRemarks, ?int $principalId
    ): void {
        $this->execute('
            INSERT INTO report_remarks
                (school_id, student_id, exam_id,
                 class_teacher_remarks, class_teacher_id,
                 principal_remarks, principal_id)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                class_teacher_remarks = COALESCE(VALUES(class_teacher_remarks), class_teacher_remarks),
                class_teacher_id      = COALESCE(VALUES(class_teacher_id), class_teacher_id),
                principal_remarks     = COALESCE(VALUES(principal_remarks), principal_remarks),
                principal_id          = COALESCE(VALUES(principal_id), principal_id)
        ', [
            $schoolId, $studentId, $examId,
            $classTeacherRemarks, $classTeacherId,
            $principalRemarks, $principalId,
        ]);
    }

    // ── Other report types ────────────────────────────────────────────────────

    public function getClassResults(int $examId): array
    {
        $rows = $this->fetchAll('
            SELECT er.student_id,
                   CONCAT(st.first_name, \' \', st.last_name) AS student_name,
                   st.admission_no,
                   SUM(er.marks)                                           AS total_marks,
                   SUM(er.max_marks)                                       AS total_max,
                   ROUND(SUM(er.marks) / NULLIF(SUM(er.max_marks), 0) * 100, 2) AS average
            FROM exam_results er
            JOIN students st ON st.id = er.student_id
            WHERE er.exam_id = ?
            GROUP BY er.student_id, st.first_name, st.last_name, st.admission_no
            ORDER BY average DESC
        ', [$examId]);

        foreach ($rows as $i => &$r) {
            $r['position'] = $i + 1;
        }
        return $rows;
    }

    public function getFeeCollection(int $schoolId, int $termId): array
    {
        return $this->fetchOne('
            SELECT
                COALESCE(SUM(fi.amount), 0)                                 AS total_billed,
                COALESCE(SUM(fp_s.total_paid), 0)                           AS total_paid,
                COUNT(CASE WHEN fi.status = \'paid\'    THEN 1 END)          AS paid_count,
                COUNT(CASE WHEN fi.status = \'unpaid\'  THEN 1 END)          AS unpaid_count,
                COUNT(CASE WHEN fi.status = \'partial\' THEN 1 END)          AS partial_count
            FROM fee_invoices fi
            JOIN students st ON st.school_id = ? AND st.id = fi.student_id
            LEFT JOIN (
                SELECT invoice_id, SUM(amount_paid) AS total_paid
                FROM fee_payments GROUP BY invoice_id
            ) fp_s ON fp_s.invoice_id = fi.id
            WHERE fi.term_id = ?
        ', [$schoolId, $termId]) ?? [];
    }

    public function getAttendanceSummary(int $schoolId, int $termId): array
    {
        return $this->fetchAll('
            SELECT t.id AS term_id, c.id AS class_id, c.name AS class_name,
                   COUNT(DISTINCT a.date)                                         AS total_days,
                   COALESCE(ROUND(AVG(CASE WHEN a.status = \'present\' THEN 100.0 ELSE 0 END), 2), 0) AS avg_present
            FROM attendance a
            JOIN classes c ON c.id = a.class_id
            JOIN terms   t ON t.id = a.term_id
            WHERE c.school_id = ? AND a.term_id = ?
            GROUP BY t.id, c.id, c.name
            ORDER BY c.name
        ', [$schoolId, $termId]);
    }

    public function getSubjectPerformance(int $examId): array
    {
        return $this->fetchAll('
            SELECT er.subject_id, sub.name AS subject_name, sub.code AS subject_code,
                   COUNT(*)                                                     AS entry_count,
                   ROUND(AVG(er.marks / NULLIF(er.max_marks, 0) * 100), 2)     AS avg_score,
                   ROUND(MIN(er.marks / NULLIF(er.max_marks, 0) * 100), 2)     AS min_score,
                   ROUND(MAX(er.marks / NULLIF(er.max_marks, 0) * 100), 2)     AS max_score,
                   SUM(er.marks / NULLIF(er.max_marks, 0) * 100 >= 50)         AS pass_count
            FROM exam_results er
            JOIN subjects sub ON sub.id = er.subject_id
            WHERE er.exam_id = ?
            GROUP BY er.subject_id, sub.name, sub.code
            ORDER BY avg_score DESC
        ', [$examId]);
    }
}

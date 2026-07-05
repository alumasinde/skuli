<?php
declare(strict_types=1);

namespace Modules\Students\Repositories;

use Core\Repository;

final class StudentRepository extends Repository
{
    private const COLS = "
        s.id, s.school_id, s.class_id, s.admission_no,
        s.first_name, s.middle_name, s.last_name,
        s.gender, s.dob, s.nationality, s.national_id,
        s.religion, s.blood_group, s.address, s.medical_notes,
        s.photo_url, s.is_active, s.enrolled_at,
        s.left_date, s.left_reason, s.created_at, s.updated_at
    ";

    public function create(array $data): int
    {
        return $this->insert("
            INSERT INTO students
                (school_id, class_id, admission_no, first_name, middle_name, last_name,
                 gender, dob, nationality, national_id, religion, blood_group,
                 address, medical_notes, photo_url, is_active)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,1)
        ", [
            $data['school_id'], $data['class_id'], $data['admission_no'],
            $data['first_name'], $data['middle_name'] ?? '', $data['last_name'],
            $data['gender'] ?? null, $data['dob'] ?? null, $data['nationality'] ?? 'Kenyan',
            $data['national_id'] ?? null, $data['religion'] ?? null, $data['blood_group'] ?? null,
            $data['address'] ?? null, $data['medical_notes'] ?? null, $data['photo_url'] ?? '',
        ]);
    }

    /**
     * Dashboard summary for a student: fees, attendance, latest exam,
     * discipline, library, and documents. Each section runs independently
     * via safeSummary() — a failure in one (e.g. "documents" below, which
     * has no backing table at all) degrades that section gracefully
     * instead of throwing a raw PDOException and taking the whole
     * dashboard down, which is what happened before.
     */
    public function dashboardSummary(int $studentId): array
    {
        return [
            'fees'       => $this->safeSummary(fn () => $this->feeSummary($studentId)),
            'attendance' => $this->safeSummary(fn () => $this->attendanceSummary($studentId)),
            'exam'       => $this->safeSummary(fn () => $this->latestExamSummary($studentId)),
            'discipline' => $this->safeSummary(fn () => $this->disciplineSummary($studentId)),
            'library'    => $this->safeSummary(fn () => $this->librarySummary($studentId)),
            'documents'  => $this->safeSummary(fn () => $this->documentSummary($studentId)),
        ];
    }

    private function safeSummary(callable $fn): array
    {
        try {
            $result = $fn();
            return ($result ?? []) + ['available' => true];
        } catch (\Throwable $e) {
            error_log('Student dashboard summary failed: ' . $e->getMessage());
            return ['available' => false];
        }
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne("
            SELECT " . self::COLS . ", c.name AS class_name
            FROM students s
            JOIN classes c ON c.id = s.class_id
            WHERE s.id = ? AND s.deleted_at IS NULL
        ", [$id]);
    }

    /**
     * Rewritten as two independent scalar subqueries — no join, no
     * GROUP BY. The previous version's `GROUP BY p.total_paid` happened to
     * produce correct results only because this query is always scoped to
     * one student (every joined row shared an identical total_paid value),
     * but grouping by a value column rather than an ID is fragile and
     * would silently misbehave the moment this pattern got reused for a
     * multi-student report. This version has no such hidden assumption.
     */
    public function feeSummary(int $studentId): array
    {
        $row = $this->fetchOne("
            SELECT
                COALESCE((
                    SELECT SUM(fi.amount) FROM fee_invoices fi
                    WHERE fi.student_id = ? AND fi.deleted_at IS NULL
                ), 0) AS total_due,
                COALESCE((
                    SELECT SUM(fp.amount_paid)
                    FROM fee_payments fp
                    JOIN fee_invoices fi ON fi.id = fp.invoice_id
                    WHERE fi.student_id = ? AND fp.deleted_at IS NULL AND fi.deleted_at IS NULL
                ), 0) AS total_paid
        ", [$studentId, $studentId]);

        $totalDue  = (float) ($row['total_due']  ?? 0);
        $totalPaid = (float) ($row['total_paid'] ?? 0);

        return [
            'total_due'  => $totalDue,
            'total_paid' => $totalPaid,
            'balance'    => $totalDue - $totalPaid,
        ];
    }

    public function attendanceSummary(int $studentId): array
    {
        return $this->fetchOne("
            SELECT
                COUNT(*) AS total_days,
                SUM(status = 'present') AS present_days,
                SUM(status = 'absent')  AS absent_days,
                SUM(status = 'late')    AS late_days,
                ROUND((SUM(status = 'present') / NULLIF(COUNT(*), 0)) * 100, 1) AS attendance_rate
            FROM attendance
            WHERE student_id = ? AND deleted_at IS NULL
        ", [$studentId]) ?? [
            'total_days' => 0, 'present_days' => 0, 'absent_days' => 0,
            'late_days' => 0, 'attendance_rate' => null,
        ];
    }

    public function latestExamSummary(int $studentId): ?array
    {
        return $this->fetchOne("
            SELECT
                e.name AS exam_name,
                e.type AS exam_type,
                e.end_date AS exam_date,
                s.subjects,
                s.total_marks,
                s.total_possible,
                s.average,
                s.grade,
                s.position,
                s.remarks,
                s.published_at
            FROM exam_student_summaries s
            JOIN exams e ON e.id = s.exam_id
            WHERE s.student_id = ? AND e.deleted_at IS NULL
            ORDER BY e.end_date DESC, s.published_at DESC
            LIMIT 1
        ", [$studentId]);
    }

    public function disciplineSummary(int $studentId): array
    {
        return $this->fetchOne("
            SELECT COUNT(*) AS cases
            FROM discipline_records
            WHERE student_id = ? AND deleted_at IS NULL
        ", [$studentId]) ?? ['cases' => 0];
    }

    /**
     * FIXED: was querying a nonexistent `library_loans` table. Your real
     * schema has `library_books` + `library_issues` (issued_at/due_date/
     * returned_at/fine_amount) — rewritten against the real table, with
     * the deleted_at guard every other query here already has. "Borrowed"
     * means an issue row with no returned_at yet.
     */
    public function librarySummary(int $studentId): array
    {
        return $this->fetchOne("
            SELECT
                COUNT(*) AS borrowed,
                COALESCE(SUM(fine_amount), 0) AS total_fines,
                SUM(fine_amount > 0 AND fine_paid = 0) AS unpaid_fines
            FROM library_issues
            WHERE student_id = ? AND returned_at IS NULL AND deleted_at IS NULL
        ", [$studentId]) ?? ['borrowed' => 0, 'total_fines' => 0, 'unpaid_fines' => 0];
    }

    /**
     * STILL A STUB — there genuinely is no documents table anywhere in
     * your schema (checked blank.sql directly, under any name). Unlike
     * library, which now has a real backing table, this one has nothing
     * to point at yet. Left in place, safely caught by safeSummary()
     * above, so the dashboard shows "documents unavailable" rather than
     * crashing — but this needs a real `student_documents` (or similar)
     * table and a small module before it can report real data.
     */
    public function documentSummary(int $studentId): array
    {
        return $this->fetchOne("
            SELECT COUNT(*) AS documents
            FROM student_documents
            WHERE student_id = ?
        ", [$studentId]) ?? ['documents' => 0];
    }

    public function getClasses(int $schoolId): array
    {
        return $this->fetchAll("
            SELECT id, name AS class_name
            FROM classes
            WHERE school_id = ? AND deleted_at IS NULL
            ORDER BY name
        ", [$schoolId]);
    }

    public function findByAdmissionNo(string $no, int $schoolId): ?array
    {
        return $this->fetchOne("
            SELECT " . self::COLS . "
            FROM students s
            WHERE s.admission_no = ? AND s.school_id = ? AND s.deleted_at IS NULL
        ", [$no, $schoolId]);
    }

    public function listByAdmin(int $schoolId, int $page, int $perPage): array
    {
        $total = (int) $this->fetchColumn(
            'SELECT COUNT(*)
             FROM students s
             WHERE s.school_id = ?
               AND s.is_active = 1
               AND s.deleted_at IS NULL',
            [$schoolId]
        );

        $offset = ($page - 1) * $perPage;

        $list = $this->fetchAll("
            SELECT
                " . self::COLS . ",
                c.name AS class_name
            FROM students s
            LEFT JOIN classes c
                ON c.id = s.class_id
            WHERE s.school_id = ?
              AND s.is_active = 1
              AND s.deleted_at IS NULL
            ORDER BY s.first_name, s.last_name
            LIMIT ? OFFSET ?
        ", [$schoolId, $perPage, $offset]);

        return [
            'list'  => $list,
            'total' => $total,
        ];
    }

    public function listBySchool(int $schoolId, int $page, int $perPage): array
    {
        $total = (int) $this->fetchColumn(
            'SELECT COUNT(*) FROM students WHERE school_id = ? AND is_active = 1 AND deleted_at IS NULL',
            [$schoolId]
        );
        $offset = ($page - 1) * $perPage;
        $list = $this->fetchAll("
            SELECT " . self::COLS . "
            FROM students s
            WHERE s.school_id = ? AND s.is_active = 1 AND s.deleted_at IS NULL
            ORDER BY s.first_name, s.last_name
            LIMIT ? OFFSET ?
        ", [$schoolId, $perPage, $offset]);

        return ['list' => $list, 'total' => $total];
    }

    public function listByClass(int $classId): array
    {
        return $this->fetchAll("
            SELECT " . self::COLS . "
            FROM students s
            WHERE s.class_id = ? AND s.is_active = 1 AND s.deleted_at IS NULL
            ORDER BY s.first_name, s.last_name
        ", [$classId]);
    }

    public function search(int $schoolId, string $q): array
    {
        $like = "%{$q}%";
        return $this->fetchAll("
            SELECT " . self::COLS . "
            FROM students s
            WHERE s.school_id = ? AND s.is_active = 1 AND s.deleted_at IS NULL
              AND (s.first_name LIKE ? OR s.last_name LIKE ?
                   OR s.admission_no LIKE ?
                   OR CONCAT(s.first_name,' ',s.last_name) LIKE ?)
            ORDER BY s.first_name, s.last_name
            LIMIT 50
        ", [$schoolId, $like, $like, $like, $like]);
    }

    public function listByParentUser(int $userId): array
    {
        return $this->fetchAll("
            SELECT DISTINCT " . self::COLS . "
            FROM students s
            JOIN parent_student ps ON ps.student_id = s.id
            JOIN parents p ON p.id = ps.parent_id
            WHERE p.user_id = ? AND s.is_active = 1 AND s.deleted_at IS NULL
            ORDER BY s.first_name, s.last_name
        ", [$userId]);
    }

    public function listByTeacherUser(int $userId): array
    {
        return $this->fetchAll("
            SELECT DISTINCT " . self::COLS . "
            FROM students s
            JOIN teacher_subjects ts ON ts.class_id = s.class_id
            JOIN teachers t ON t.id = ts.teacher_id
            WHERE t.user_id = ? AND s.is_active = 1 AND s.deleted_at IS NULL
            ORDER BY s.first_name, s.last_name
        ", [$userId]);
    }

    public function update(int $id, array $data): bool
    {
        $affected = $this->execute("
            UPDATE students SET
                class_id = ?, first_name = ?, middle_name = ?, last_name = ?,
                gender = ?, dob = ?, nationality = ?, national_id = ?,
                religion = ?, blood_group = ?, address = ?, medical_notes = ?, photo_url = ?
            WHERE id = ? AND deleted_at IS NULL
        ", [
            $data['class_id'], $data['first_name'], $data['middle_name'] ?? '', $data['last_name'],
            $data['gender'] ?? null, $data['dob'] ?? null, $data['nationality'] ?? null,
            $data['national_id'] ?? null, $data['religion'] ?? null, $data['blood_group'] ?? null,
            $data['address'] ?? null, $data['medical_notes'] ?? null, $data['photo_url'] ?? '',
            $id,
        ]);
        return $affected > 0;
    }

    public function softDelete(int $id, int $deletedBy): bool
    {
        $affected = $this->execute("
            UPDATE students SET is_active = 0, deleted_at = NOW(), deleted_by = ?
            WHERE id = ? AND deleted_at IS NULL
        ", [$deletedBy, $id]);
        return $affected > 0;
    }

    public function isParentOfStudent(int $userId, int $studentId): bool
    {
        return \Core\Ownership::isParentOfStudent($userId, $studentId);
    }

    public function isTeacherOfStudent(int $userId, int $studentId): bool
    {
        return \Core\Ownership::isTeacherOfStudent($userId, $studentId);
    }

    public function getParentsByStudent(int $studentId): array
    {
        return $this->fetchAll("
            SELECT p.id, u.first_name, u.last_name, u.email,
                   COALESCE(p.phone,'') AS phone, ps.relationship
            FROM parent_student ps
            JOIN parents p ON p.id = ps.parent_id
            JOIN users   u ON u.id = p.user_id
            WHERE ps.student_id = ?
            ORDER BY u.first_name, u.last_name
        ", [$studentId]);
    }
}
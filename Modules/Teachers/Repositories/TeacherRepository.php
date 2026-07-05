<?php
declare(strict_types=1);

namespace Modules\Teachers\Repositories;

use Core\Repository;

final class TeacherRepository extends Repository
{
    public function create(array $d): int
    {
        return $this->insert('
            INSERT INTO teachers
                (user_id, school_id, employee_no, phone, gender, dob, qualification, tsc_no,
                 specialization, hire_date, employment_type, is_class_teacher, national_id,
                 address, photo_url)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)
        ', [
            $d['user_id'], $d['school_id'], $d['employee_no'], $d['phone'] ?? null,
            $d['gender'] ?? null, $d['dob'] ?? null, $d['qualification'] ?? null, $d['tsc_no'] ?? null,
            $d['specialization'] ?? null, $d['hire_date'] ?? null, $d['employment_type'] ?? 'permanent',
            (int) ($d['is_class_teacher'] ?? 0), $d['national_id'] ?? null, $d['address'] ?? null,
            $d['photo_url'] ?? null,
        ]);
    }

    public function listBySchool(int $schoolId): array
    {
        return $this->fetchAll('
            SELECT t.*, u.first_name, u.last_name, u.email,
                   (SELECT COUNT(*) FROM teacher_subjects ts
                     WHERE ts.teacher_id = t.id AND ts.deleted_at IS NULL) AS subject_count
            FROM teachers t
            JOIN users u ON u.id = t.user_id
            WHERE t.school_id = ? AND t.is_active = 1 AND t.deleted_at IS NULL
            ORDER BY u.first_name, u.last_name
        ', [$schoolId]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('
            SELECT t.*, u.first_name, u.last_name, u.email
            FROM teachers t
            JOIN users u ON u.id = t.user_id
            WHERE t.id = ? AND t.deleted_at IS NULL
        ', [$id]);
    }

    /** Is this employee number already used by another teacher in the school? */
    public function employeeNoExists(int $schoolId, string $employeeNo, int $excludeId = 0): bool
    {
        $row = $this->fetchOne('
            SELECT id FROM teachers
            WHERE school_id = ? AND employee_no = ? AND deleted_at IS NULL AND id <> ?
            LIMIT 1
        ', [$schoolId, $employeeNo, $excludeId]);

        return $row !== null;
    }

    /** Does this user already have a teacher record in this school? */
    public function userHasTeacher(int $schoolId, int $userId, int $excludeId = 0): bool
    {
        $row = $this->fetchOne('
            SELECT id FROM teachers
            WHERE school_id = ? AND user_id = ? AND deleted_at IS NULL AND id <> ?
            LIMIT 1
        ', [$schoolId, $userId, $excludeId]);

        return $row !== null;
    }

    /**
     * Users in this school who don't yet have a teacher record — the pool of
     * accounts that can be linked when enrolling a new teacher. When editing,
     * pass $includeUserId to keep the current teacher's own user in the list.
     */
    public function usersWithoutTeacher(int $schoolId, int $includeUserId = 0): array
    {
        return $this->fetchAll('
            SELECT u.id, u.first_name, u.last_name, u.email
            FROM users u
            WHERE u.school_id = ? AND u.deleted_at IS NULL
              AND (
                    u.id = ?
                 OR u.id NOT IN (
                        SELECT t.user_id FROM teachers t
                        WHERE t.school_id = ? AND t.deleted_at IS NULL
                    )
                  )
            ORDER BY u.first_name, u.last_name
        ', [$schoolId, $includeUserId, $schoolId]);
    }

    public function update(int $id, array $d): void
    {
        // photo_url is only overwritten when a new value is supplied, so an
        // edit that doesn't touch the photo keeps the existing one.
        $this->execute('
            UPDATE teachers SET
                phone = ?, gender = ?, dob = ?, qualification = ?, tsc_no = ?,
                specialization = ?, hire_date = ?, employment_type = ?,
                is_class_teacher = ?, national_id = ?, address = ?, photo_url = ?
            WHERE id = ? AND deleted_at IS NULL
        ', [
            $d['phone'] ?? null, $d['gender'] ?? null, $d['dob'] ?? null, $d['qualification'] ?? null,
            $d['tsc_no'] ?? null, $d['specialization'] ?? null, $d['hire_date'] ?? null,
            $d['employment_type'] ?? 'permanent', (int) ($d['is_class_teacher'] ?? 0),
            $d['national_id'] ?? null, $d['address'] ?? null, $d['photo_url'] ?? null,
            $id,
        ]);
    }

    public function softDelete(int $id): void
    {
        $this->execute('UPDATE teachers SET is_active = 0, deleted_at = NOW() WHERE id = ?', [$id]);
    }

    // ── Subject assignments (teacher_subjects) ───────────────────────────────

    public function assignSubject(int $teacherId, int $subjectId, int $classId): void
    {
        // uq_ts (teacher_id, subject_id, class_id) makes this idempotent, but a
        // previously-removed row is soft-deleted — resurrect it if present.
        $existing = $this->fetchOne('
            SELECT id, deleted_at FROM teacher_subjects
            WHERE teacher_id = ? AND subject_id = ? AND class_id = ?
            LIMIT 1
        ', [$teacherId, $subjectId, $classId]);

        if ($existing === null) {
            $this->execute('
                INSERT INTO teacher_subjects (teacher_id, subject_id, class_id) VALUES (?,?,?)
            ', [$teacherId, $subjectId, $classId]);
        } elseif ($existing['deleted_at'] !== null) {
            $this->execute('UPDATE teacher_subjects SET deleted_at = NULL WHERE id = ?', [(int) $existing['id']]);
        }
    }

    public function removeSubject(int $teacherId, int $subjectId, int $classId): void
    {
        $this->execute('
            UPDATE teacher_subjects SET deleted_at = NOW()
            WHERE teacher_id = ? AND subject_id = ? AND class_id = ?
        ', [$teacherId, $subjectId, $classId]);
    }

    public function getSubjects(int $teacherId): array
    {
        return $this->fetchAll('
            SELECT ts.id, ts.subject_id, ts.class_id,
                   s.name AS subject_name, s.code AS subject_code,
                   c.name AS class_name
            FROM teacher_subjects ts
            JOIN subjects s ON s.id = ts.subject_id
            JOIN classes  c ON c.id = ts.class_id
            WHERE ts.teacher_id = ? AND ts.deleted_at IS NULL
            ORDER BY c.name, s.name
        ', [$teacherId]);
    }
}
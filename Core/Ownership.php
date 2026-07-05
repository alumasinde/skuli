<?php
declare(strict_types=1);

namespace Core;

use PDO;

/**
 * Ownership — row-level access guard for student-scoped data (fees,
 * results, attendance, discipline). Direct port of ownership.go from the
 * Go backend, which was added after discovering that finance/exams/
 * attendance/discipline endpoints had NO ownership check at all — any
 * parent with the base permission could read any other family's data by
 * changing the student ID in the URL.
 *
 * This is the single source of truth for that check; every controller
 * method that returns one student's personal data MUST call
 * Ownership::canAccessStudent() before returning, the same way every Go
 * handler was updated to call ownership.CanAccessStudent().
 */
final class Ownership
{
    public static function canAccessStudent(int $studentId): bool
    {
        $roles  = RequestContext::roles();
        $userId = RequestContext::userId();

        $isParent  = in_array('parent', $roles, true);
        $isTeacher = in_array('teacher', $roles, true);

        // Admin (or any role that's neither parent nor teacher) already
        // passed the permission middleware check — allow through.
        if (!$isParent && !$isTeacher) {
            return true;
        }

        if ($isParent && self::isParentOfStudent($userId, $studentId)) {
            return true;
        }
        if ($isTeacher && self::isTeacherOfStudent($userId, $studentId)) {
            return true;
        }

        Logger::audit('ownership check failed', [
            'user_id'    => $userId,
            'roles'      => $roles,
            'student_id' => $studentId,
        ]);
        return false;
    }

    public static function isParentOfStudent(int $userId, int $studentId): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM parent_student ps
            JOIN parents p ON p.id = ps.parent_id
            WHERE p.user_id = ? AND ps.student_id = ?
        ');
        $stmt->execute([$userId, $studentId]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public static function isTeacherOfStudent(int $userId, int $studentId): bool
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('
            SELECT COUNT(*) FROM students s
            JOIN teacher_subjects ts ON ts.class_id = s.class_id
            JOIN teachers t          ON t.id = ts.teacher_id
            WHERE t.user_id = ? AND s.id = ?
        ');
        $stmt->execute([$userId, $studentId]);
        return (int)$stmt->fetchColumn() > 0;
    }
}

<?php
declare(strict_types=1);

namespace Modules\Classes\Repositories;

use Core\Repository;

final class ClassRepository extends Repository
{
    public function create(array $d): int
    {
        return $this->insert(
            'INSERT INTO classes (school_id, name, level, stream) VALUES (?, ?, ?, ?)',
            [$d['school_id'], $d['name'], $d['level'], $d['stream'] ?? null]
        );
    }

    public function listBySchool(int $sid): array
    {
        return $this->fetchAll('
            SELECT
                c.id,
                c.school_id,
                c.name AS class_name,
                c.level,
                c.stream,
                c.created_at,
                c.updated_at,
                (
                    SELECT COUNT(*)
                    FROM students s
                    WHERE s.class_id = c.id
                      AND s.is_active = 1
                ) AS student_count
            FROM classes c
            WHERE c.school_id = ?
              AND c.deleted_at IS NULL
            ORDER BY c.level, c.name
        ', [$sid]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('
            SELECT
                c.id,
                c.school_id,
                c.name AS class_name,
                c.level,
                c.stream,
                c.description,
                c.created_at,
                c.updated_at,
                (
                    SELECT COUNT(*)
                    FROM students s
                    WHERE s.class_id = c.id
                      AND s.is_active = 1
                ) AS student_count
            FROM classes c
            WHERE c.id = ?
              AND c.deleted_at IS NULL
        ', [$id]);
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            'UPDATE classes SET name = ?, level = ?, stream = ? WHERE id = ?',
            [$d['name'], $d['level'], $d['stream'] ?? null, $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('UPDATE classes SET deleted_at = NOW() WHERE id = ?', [$id]);
    }

    public function getSubjects(int $classId): array
    {
        return $this->fetchAll('
            SELECT cs.*, s.name AS subject_name, s.code AS subject_code
            FROM class_subjects cs
            JOIN subjects s ON s.id = cs.subject_id
            WHERE cs.class_id = ?
              AND cs.deleted_at IS NULL
        ', [$classId]);
    }

    public function assignSubject(int $classId, int $subjectId, int $compulsory): void
    {
        $this->execute(
            'INSERT INTO class_subjects (class_id, subject_id, is_compulsory)
             VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE deleted_at = NULL, is_compulsory = VALUES(is_compulsory)',
            [$classId, $subjectId, $compulsory]
        );
    }

    public function removeSubject(int $classId, int $subjectId): void
    {
        $this->execute(
            'UPDATE class_subjects SET deleted_at = NOW() WHERE class_id = ? AND subject_id = ?',
            [$classId, $subjectId]
        );
    }

    public function getStudentCount(int $classId): int
    {
        return (int) $this->fetchColumn(
            'SELECT COUNT(*) FROM students WHERE class_id = ? AND is_active = 1',
            [$classId]
        );
    }
}
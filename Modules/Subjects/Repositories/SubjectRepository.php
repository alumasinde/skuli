<?php
declare(strict_types=1);

namespace Modules\Subjects\Repositories;

use Core\Repository;

final class SubjectRepository extends Repository
{
    public function create(array $d): int
    {
        return $this->insert(
            'INSERT INTO subjects (school_id, name, code, is_active) VALUES (?,?,?,1)',
            [$d['school_id'], $d['name'], $d['code']]
        );
    }

    public function listBySchool(int $sid): array
    {
        // Also count how many classes each subject is assigned to, so the list
        // view can show usage without an N+1 query.
        return $this->fetchAll('
            SELECT s.*,
                   (SELECT COUNT(*) FROM class_subjects cs
                     WHERE cs.subject_id = s.id AND cs.deleted_at IS NULL) AS class_count
            FROM subjects s
            WHERE s.school_id = ? AND s.is_active = 1 AND s.deleted_at IS NULL
            ORDER BY s.name
        ', [$sid]);
    }

    public function findById(int $id): ?array
    {
        // Guard against returning a soft-deleted row.
        return $this->fetchOne(
            'SELECT * FROM subjects WHERE id = ? AND deleted_at IS NULL',
            [$id]
        );
    }

    /**
     * Is a given code already used by another subject in the same school?
     * Pass $excludeId when checking during an update so a subject doesn't
     * collide with itself.
     */
    public function codeExists(int $schoolId, string $code, int $excludeId = 0): bool
    {
        $row = $this->fetchOne('
            SELECT id FROM subjects
            WHERE school_id = ? AND code = ? AND deleted_at IS NULL AND id <> ?
            LIMIT 1
        ', [$schoolId, $code, $excludeId]);

        return $row !== null;
    }

    /** Classes this subject is assigned to (for the show page). */
    public function classesUsing(int $subjectId): array
    {
        return $this->fetchAll('
            SELECT c.id, c.name, cs.is_compulsory
            FROM class_subjects cs
            JOIN classes c ON c.id = cs.class_id
            WHERE cs.subject_id = ? AND cs.deleted_at IS NULL
            ORDER BY c.name
        ', [$subjectId]);
    }

    public function update(int $id, array $d): void
    {
        $this->execute(
            'UPDATE subjects SET name = ?, code = ? WHERE id = ? AND deleted_at IS NULL',
            [$d['name'], $d['code'], $id]
        );
    }

    public function delete(int $id): void
    {
        $this->execute(
            'UPDATE subjects SET is_active = 0, deleted_at = NOW() WHERE id = ?',
            [$id]
        );
    }
}
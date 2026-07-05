<?php
declare(strict_types=1);

namespace Modules\Parents\Repositories;

use Core\Repository;

final class ParentRepository extends Repository
{
    public function listBySchool(int $schoolId): array
    {
        return $this->fetchAll("
            SELECT p.id, p.user_id, p.school_id, p.phone, p.occupation, p.address,
                   u.first_name, u.last_name, u.email,
                   (SELECT COUNT(*) FROM parent_student ps WHERE ps.parent_id = p.id) AS child_count
            FROM parents p
            JOIN users u ON u.id = p.user_id
            WHERE p.school_id = ? AND p.deleted_at IS NULL
            ORDER BY u.first_name, u.last_name
        ", [$schoolId]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne("
            SELECT p.id, p.user_id, p.school_id, p.phone, p.occupation, p.address,
                   u.first_name, u.last_name, u.email
            FROM parents p
            JOIN users u ON u.id = p.user_id
            WHERE p.id = ? AND p.deleted_at IS NULL
        ", [$id]);
    }


    //Linkable Parents 

public function linkableParents(int $studentId, int $schoolId): array
{
    return $this->fetchAll("
        SELECT p.id, u.first_name, u.last_name, u.email, p.phone
        FROM parents p
        JOIN users u ON u.id = p.user_id
        WHERE p.school_id = ? AND p.deleted_at IS NULL
          AND p.id NOT IN (
              SELECT ps.parent_id FROM parent_student ps WHERE ps.student_id = ?
          )
        ORDER BY u.first_name, u.last_name
    ", [$schoolId, $studentId]);
}
    public function findByUserId(int $userId): ?array
    {
        return $this->fetchOne("
            SELECT p.id, p.user_id, p.school_id, p.phone, p.occupation, p.address,
                   u.first_name, u.last_name, u.email
            FROM parents p
            JOIN users u ON u.id = p.user_id
            WHERE p.user_id = ? AND p.deleted_at IS NULL
        ", [$userId]);
    }

    /** New: creates a parents row linking an existing user account. */
    public function create(array $data): int
    {
        return $this->insert("
            INSERT INTO parents (user_id, school_id, phone, occupation, address)
            VALUES (?, ?, ?, ?, ?)
        ", [
            $data['user_id'], $data['school_id'],
            $data['phone'] ?? null, $data['occupation'] ?? null, $data['address'] ?? null,
        ]);
    }

    /**
     * New: users in this school who don't already have a parents row —
     * the pool of accounts that can be turned into a parent record. Same
     * pattern as TeacherRepository::usersWithoutTeacher from earlier.
     */
    public function usersWithoutParent(int $schoolId): array
    {
        return $this->fetchAll("
            SELECT u.id, u.first_name, u.last_name, u.email
            FROM users u
            WHERE u.school_id = ? AND u.deleted_at IS NULL
              AND u.id NOT IN (
                  SELECT p.user_id FROM parents p
                  WHERE p.school_id = ? AND p.deleted_at IS NULL
              )
            ORDER BY u.first_name, u.last_name
        ", [$schoolId, $schoolId]);
    }

    public function update(int $id, array $data): bool
    {
        $affected = $this->execute("
            UPDATE parents SET phone = ?, occupation = ?, address = ?
            WHERE id = ? AND deleted_at IS NULL
        ", [$data['phone'] ?? null, $data['occupation'] ?? null, $data['address'] ?? null, $id]);
        return $affected > 0;
    }

    /**
     * New: every student linked to this parent, with class name — needed
     * for the parent's profile page ("My Children" / admin's parent detail
     * view). Didn't exist before; the API controller never needed it since
     * nothing surfaced a parent's linked children anywhere.
     */
    public function getLinkedStudents(int $parentId): array
    {
        return $this->fetchAll("
            SELECT s.id, s.first_name, s.last_name, s.admission_no, s.photo_url,
                   c.name AS class_name, ps.relationship
            FROM parent_student ps
            JOIN students s ON s.id = ps.student_id
            JOIN classes  c ON c.id = s.class_id
            WHERE ps.parent_id = ? AND s.deleted_at IS NULL
            ORDER BY s.first_name, s.last_name
        ", [$parentId]);
    }

    public function getUnlinkedStudents(int $schoolId, int $parentId): array
{
    return $this->fetchAll("
        SELECT
            s.id,
            s.first_name,
            s.last_name,
            s.admission_no,
            c.name AS class_name
        FROM students s
        LEFT JOIN classes c
            ON c.id = s.class_id
        WHERE s.school_id = ?
          AND s.deleted_at IS NULL
          AND s.id NOT IN (
                SELECT student_id
                FROM parent_student
                WHERE parent_id = ?
          )
        ORDER BY s.first_name, s.last_name
    ", [$schoolId, $parentId]);
}

    public function linkStudent(int $parentId, int $studentId, string $relationship): bool
    {
        $affected = $this->execute("
            INSERT IGNORE INTO parent_student (parent_id, student_id, relationship)
            VALUES (?, ?, ?)
        ", [$parentId, $studentId, $relationship]);
        return $affected > 0;
    }

    public function unlinkStudent(int $parentId, int $studentId): bool
    {
        $affected = $this->execute(
            'DELETE FROM parent_student WHERE parent_id = ? AND student_id = ?',
            [$parentId, $studentId]
        );
        return $affected > 0;
    }

    /**
     * New: was completely absent before — linkStudent() had no tenant
     * check at all, so a parent from school A could be linked to a
     * student in school B given the right IDs. Used by the service to
     * validate both sides belong to the same school before linking.
     */
    public function parentSchoolId(int $parentId): ?int
    {
        $row = $this->fetchOne('SELECT school_id FROM parents WHERE id = ? AND deleted_at IS NULL', [$parentId]);
        return $row ? (int) $row['school_id'] : null;
    }

    public function studentSchoolId(int $studentId): ?int
    {
        $row = $this->fetchOne('SELECT school_id FROM students WHERE id = ? AND deleted_at IS NULL', [$studentId]);
        return $row ? (int) $row['school_id'] : null;
    }
}
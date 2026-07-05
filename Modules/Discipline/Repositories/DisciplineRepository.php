<?php
declare(strict_types=1);

namespace Modules\Discipline\Repositories;

use Core\Repository;

/**
 * DisciplineRepository — PHP port with one bug fixed vs the Go source:
 * the Go disciplinerepo.go had `u.name AS recorder_name` in dCols, but
 * the users table has no `name` column (dropped during the schema migration
 * to first_name+last_name). Fixed here to CONCAT(u.first_name,' ',u.last_name).
 */
final class DisciplineRepository extends Repository
{
    private const COLS = "
        dr.id, dr.school_id, dr.student_id, dr.term_id,
        dr.incident_date, dr.type, dr.description, dr.action_taken,
        dr.recorded_by, dr.created_at,
        CONCAT(s.first_name, ' ', s.last_name) AS student_name,
        s.admission_no, t.name AS term_name,
        CONCAT(u.first_name, ' ', u.last_name) AS recorder_name
    ";

    private const JOIN = "
        FROM discipline_records dr
        JOIN students s ON s.id = dr.student_id
        JOIN terms    t ON t.id = dr.term_id
        JOIN users    u ON u.id = dr.recorded_by
    ";

    public function create(array $data): int
    {
        return $this->insert('
            INSERT INTO discipline_records
                (school_id, student_id, term_id, incident_date, type,
                 description, action_taken, recorded_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            $data['school_id'], $data['student_id'], $data['term_id'],
            $data['incident_date'], $data['type'],
            $data['description'], $data['action_taken'] ?? null,
            $data['recorded_by'],
        ]);
    }

    public function listBySchool(int $schoolId, int $termId = 0): array
    {
        $sql    = 'SELECT ' . self::COLS . self::JOIN . ' WHERE dr.school_id = ?';
        $params = [$schoolId];
        if ($termId > 0) { $sql .= ' AND dr.term_id = ?'; $params[] = $termId; }
        $sql .= ' ORDER BY dr.incident_date DESC';
        return $this->fetchAll($sql, $params);
    }

    public function listByStudent(int $studentId): array
    {
        return $this->fetchAll(
            'SELECT ' . self::COLS . self::JOIN . ' WHERE dr.student_id = ? ORDER BY dr.incident_date DESC',
            [$studentId]
        );
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM discipline_records WHERE id = ?', [$id]);
    }
}

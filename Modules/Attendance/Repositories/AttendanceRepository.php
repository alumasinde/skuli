<?php
declare(strict_types=1);

namespace Modules\Attendance\Repositories;

use Core\Repository;

final class AttendanceRepository extends Repository
{
    public function bulkUpsert(array $records): void
    {
        $pdo = $this->db;
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('
                INSERT INTO attendance (student_id, class_id, term_id, recorded_by, date, status, remark)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE
                    status = VALUES(status), remark = VALUES(remark),
                    recorded_by = VALUES(recorded_by)
            ');
            foreach ($records as $r) {
                $stmt->execute([
                    $r['student_id'], $r['class_id'], $r['term_id'],
                    $r['recorded_by'], $r['date'], $r['status'],
                    $r['remark'] ?? null,
                ]);
            }
            $pdo->commit();
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }

    public function listByClassDate(int $classId, string $date): array
    {
        return $this->fetchAll(
            'SELECT * FROM attendance WHERE class_id = ? AND date = ?',
            [$classId, $date]
        );
    }

    public function listByStudent(int $studentId, int $termId): array
    {
        $sql    = 'SELECT * FROM attendance WHERE student_id = ?';
        $params = [$studentId];
        if ($termId > 0) { $sql .= ' AND term_id = ?'; $params[] = $termId; }
        $sql .= ' ORDER BY date';
        return $this->fetchAll($sql, $params);
    }

    public function summaryByClass(int $classId, int $termId): array
    {
        return $this->fetchAll('
            SELECT a.student_id,
                   CONCAT(s.first_name, \' \', s.last_name) AS student_name,
                   COUNT(*)                                    AS total,
                   SUM(a.status = \'present\')                AS present,
                   SUM(a.status = \'absent\')                 AS absent,
                   SUM(a.status = \'late\')                   AS late,
                   ROUND(SUM(a.status = \'present\') / COUNT(*) * 100, 2) AS percent
            FROM attendance a
            JOIN students s ON s.id = a.student_id
            WHERE a.class_id = ? AND a.term_id = ?
            GROUP BY a.student_id, s.first_name, s.last_name
            ORDER BY s.first_name, s.last_name
        ', [$classId, $termId]);
    }
}

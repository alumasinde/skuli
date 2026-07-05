<?php
declare(strict_types=1);

namespace Modules\Attendance\Services;

use Modules\Attendance\Repositories\AttendanceRepository;

final class AttendanceService
{
    public function __construct(private readonly AttendanceRepository $repo)
    {
    }

    public function mark(array $data, int $recordedBy): void
    {
        $classId = (int)($data['class_id'] ?? 0);
        $termId  = (int)($data['term_id']  ?? 0);
        $date    = $data['date'] ?? '';
        $records = [];

        foreach ($data['records'] ?? [] as $r) {
            $records[] = [
                'student_id'  => (int)$r['student_id'],
                'class_id'    => $classId,
                'term_id'     => $termId,
                'recorded_by' => $recordedBy,
                'date'        => $date,
                'status'      => $r['status'],
                'remark'      => $r['remark'] ?? null,
            ];
        }

        if (empty($records)) {
            throw new \InvalidArgumentException('No attendance records provided.');
        }

        $this->repo->bulkUpsert($records);
    }

    public function getByClassDate(int $classId, string $date): array
    {
        return $this->repo->listByClassDate($classId, $date);
    }

    public function getByStudent(int $studentId, int $termId = 0): array
    {
        return $this->repo->listByStudent($studentId, $termId);
    }

    public function getSummary(int $classId, int $termId): array
    {
        return $this->repo->summaryByClass($classId, $termId);
    }
}

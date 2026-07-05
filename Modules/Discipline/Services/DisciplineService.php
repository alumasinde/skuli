<?php
declare(strict_types=1);

namespace Modules\Discipline\Services;

use Modules\Discipline\Repositories\DisciplineRepository;

final class DisciplineService
{
    public function __construct(private readonly DisciplineRepository $repo)
    {
    }

    public function create(array $data, int $schoolId, int $recordedBy): array
    {
        $data['school_id']   = $schoolId;
        $data['recorded_by'] = $recordedBy;
        $id = $this->repo->create($data);
        return ['id' => $id] + $data;
    }

    public function listBySchool(int $schoolId, int $termId = 0): array
    {
        return $this->repo->listBySchool($schoolId, $termId);
    }

    public function listByStudent(int $studentId): array
    {
        return $this->repo->listByStudent($studentId);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }
}

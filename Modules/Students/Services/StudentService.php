<?php
declare(strict_types=1);

namespace Modules\Students\Services;

use Modules\Students\Repositories\StudentRepository;
use Modules\Parents\Services\ParentService;

final class StudentService
{
    public function __construct(
        private StudentRepository $repo,
        private AdmissionNumberService $admissionService,
        private ParentService $parents
    ) {}

    public function create(array $data): array
    {
        $data['admission_no'] = $this->admissionService->generate((int)$data['school_id']);

        $id = $this->repo->create($data);

        return $this->repo->findById($id);
    }

    public function dashboardSummary(int $studentId): array
{
    return $this->repo->dashboardSummary($studentId);
}

public function studentProfile(int $studentId): array
{
    $student = $this->repo->findById($studentId);

    if (!$student) {
        throw new \RuntimeException('Student not found.');
    }

    $student['title'] = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')) ?: 'Student Profile';

    $student['parents'] = $this->repo->getParentsByStudent($studentId);

    $student['dashboard'] = $this->repo->dashboardSummary($studentId);
    
    $student['availableParents'] =
    $this->parents->linkableParents(
        $studentId,
        $student['school_id']
    );

    return $student;
}

    public function getClasses(int $schoolId): array
{
    return $this->repo->getClasses($schoolId);
}
    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function list(int $schoolId, int $page, int $perPage): array
    {
        return $this->repo->listBySchool($schoolId, $page, $perPage);
    }

    public function listByClass(int $classId): array
    {
        return $this->repo->listByClass($classId);
    }

    public function search(int $schoolId, string $q): array
    {
        return $this->repo->search($schoolId, $q);
    }

    public function listByAdmin(int $schoolId, int $page, int $perPage): array
    {
        return $this->repo->listByAdmin($schoolId, $page, $perPage);
    }

    public function listByParentUser(int $userId): array
    {
        return $this->repo->listByParentUser($userId);
    }

    public function listByTeacherUser(int $userId): array
    {
        return $this->repo->listByTeacherUser($userId);
    }

    public function update(int $id, array $data): bool
    {
        return $this->repo->update($id, $data);
    }

    public function deactivate(int $id, int $actorId): bool
    {
        return $this->repo->softDelete($id, $actorId);
    }

    public function isParentOfStudent(int $userId, int $studentId): bool
    {
        return $this->repo->isParentOfStudent($userId, $studentId);
    }

    public function isTeacherOfStudent(int $userId, int $studentId): bool
    {
        return $this->repo->isTeacherOfStudent($userId, $studentId);
    }

    public function getParents(int $studentId): array
    {
        return $this->repo->getParentsByStudent($studentId);
    }
}
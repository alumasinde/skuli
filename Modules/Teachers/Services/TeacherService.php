<?php
declare(strict_types=1);

namespace Modules\Teachers\Services;

use Modules\Teachers\Repositories\TeacherRepository;

final class TeacherService
{
    public function __construct(private readonly TeacherRepository $repo) {}

    public function create(array $d): array
    {
        $schoolId   = (int) ($d['school_id'] ?? 0);
        $userId     = (int) ($d['user_id'] ?? 0);
        $employeeNo = trim((string) ($d['employee_no'] ?? ''));

        if ($userId === 0) {
            throw new \InvalidArgumentException('A user account must be selected for the teacher.');
        }
        if ($employeeNo === '') {
            throw new \InvalidArgumentException('Employee number is required.');
        }
        if ($this->repo->userHasTeacher($schoolId, $userId)) {
            throw new \RuntimeException('That user is already registered as a teacher.');
        }
        if ($this->repo->employeeNoExists($schoolId, $employeeNo)) {
            throw new \RuntimeException("Employee number \"{$employeeNo}\" is already in use.");
        }

        $d['employee_no'] = $employeeNo;
        $id = $this->repo->create($d);

        return $this->repo->findById($id);
    }

    public function list(int $schoolId): array
    {
        return $this->repo->listBySchool($schoolId);
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    /** Users that can be linked as a teacher (optionally keep the current one). */
    public function linkableUsers(int $schoolId, int $includeUserId = 0): array
    {
        return $this->repo->usersWithoutTeacher($schoolId, $includeUserId);
    }

    public function update(int $id, array $d): void
    {
        $this->repo->update($id, $d);
    }

    public function deactivate(int $id): void
    {
        $this->repo->softDelete($id);
    }

    // ── Subject assignments ──────────────────────────────────────────────────

    public function assignSubject(int $teacherId, int $subjectId, int $classId): void
    {
        if ($subjectId === 0 || $classId === 0) {
            throw new \InvalidArgumentException('Both a subject and a class are required.');
        }
        $this->repo->assignSubject($teacherId, $subjectId, $classId);
    }

    public function removeSubject(int $teacherId, int $subjectId, int $classId): void
    {
        $this->repo->removeSubject($teacherId, $subjectId, $classId);
    }

    public function getSubjects(int $teacherId): array
    {
        return $this->repo->getSubjects($teacherId);
    }
}
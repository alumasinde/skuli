<?php
declare(strict_types=1);

namespace Modules\Parents\Services;

use Modules\Parents\Repositories\ParentRepository;

final class ParentService
{
    public function __construct(private readonly ParentRepository $repo) {}

    public function list(int $schoolId): array
    {
        return $this->repo->listBySchool($schoolId);
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function getOwnRecord(int $userId): ?array
    {
        return $this->repo->findByUserId($userId);
    }

    /** New: creates a parent record for an existing user account. */
    public function create(int $userId, int $schoolId, array $data): array
    {
        if ($this->repo->findByUserId($userId)) {
            throw new \RuntimeException('This user already has a parent record.');
        }

        $id = $this->repo->create([
            'user_id'    => $userId,
            'school_id'  => $schoolId,
            'phone'      => trim($data['phone'] ?? ''),
            'occupation' => trim($data['occupation'] ?? ''),
            'address'    => trim($data['address'] ?? ''),
        ]);

        return $this->repo->findById($id);
    }

    /** New: users that can be turned into a parent record. */
    public function linkableUsers(int $schoolId): array
    {
        return $this->repo->usersWithoutParent($schoolId);
    }

public function linkableParents(int $studentId, int $schoolId): array
{
    return $this->repo->linkableParents($studentId, $schoolId);
}
 
    public function update(int $id, array $data): bool
    {
        return $this->repo->update($id, $data);
    }

    /** New: a parent's linked children, for the profile/detail page. */
    public function getLinkedStudents(int $parentId): array
    {
        return $this->repo->getLinkedStudents($parentId);
    }
public function getUnlinkedStudents(int $schoolId, int $parentId): array
{
    return $this->repo->getUnlinkedStudents($schoolId, $parentId);
}
    public function linkStudent(int $parentId, int $studentId, string $relationship, int $schoolId): bool
    {
        $parentSchool  = $this->repo->parentSchoolId($parentId);
        $studentSchool = $this->repo->studentSchoolId($studentId);

        if ($parentSchool !== $schoolId || $studentSchool !== $schoolId) {
            throw new \InvalidArgumentException('Parent or student not found in this school.');
        }

        return $this->repo->linkStudent($parentId, $studentId, $relationship);
    }

    public function unlinkStudent(int $parentId, int $studentId, int $schoolId): bool
    {
        if ($this->repo->parentSchoolId($parentId) !== $schoolId) {
            throw new \InvalidArgumentException('Parent not found in this school.');
        }
        return $this->repo->unlinkStudent($parentId, $studentId);
    }
}
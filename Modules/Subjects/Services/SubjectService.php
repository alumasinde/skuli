<?php
declare(strict_types=1);

namespace Modules\Subjects\Services;

use Modules\Subjects\Repositories\SubjectRepository;

final class SubjectService
{
    public function __construct(private readonly SubjectRepository $repo) {}

    public function create(array $d): array
    {
        $schoolId = (int) $d['school_id'];
        $name     = trim((string) ($d['name'] ?? ''));
        $code     = strtoupper(trim((string) ($d['code'] ?? '')));

        if ($name === '') {
            throw new \InvalidArgumentException('Subject name is required.');
        }
        if ($code === '') {
            throw new \InvalidArgumentException('Subject code is required.');
        }
        if ($this->repo->codeExists($schoolId, $code)) {
            throw new \RuntimeException("A subject with code \"{$code}\" already exists.");
        }

        $id = $this->repo->create([
            'school_id' => $schoolId,
            'name'      => $name,
            'code'      => $code,
        ]);

        return $this->repo->findById($id);
    }

    public function list(int $sid): array
    {
        return $this->repo->listBySchool($sid);
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    /** Classes this subject is taught in — used by the show page. */
    public function classesUsing(int $subjectId): array
    {
        return $this->repo->classesUsing($subjectId);
    }

    public function update(int $id, array $d): void
    {
        $existing = $this->repo->findById($id);
        if (!$existing) {
            throw new \RuntimeException('Subject not found.');
        }

        $schoolId = (int) $existing['school_id'];
        $name     = trim((string) ($d['name'] ?? ''));
        $code     = strtoupper(trim((string) ($d['code'] ?? '')));

        if ($name === '') {
            throw new \InvalidArgumentException('Subject name is required.');
        }
        if ($code === '') {
            throw new \InvalidArgumentException('Subject code is required.');
        }
        if ($this->repo->codeExists($schoolId, $code, $id)) {
            throw new \RuntimeException("Another subject already uses code \"{$code}\".");
        }

        $this->repo->update($id, ['name' => $name, 'code' => $code]);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }
}
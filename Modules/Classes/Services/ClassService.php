<?php declare(strict_types=1);
namespace Modules\Classes\Services;
use Modules\Classes\Repositories\ClassRepository;

final class ClassService
{
    public function __construct(private readonly ClassRepository $repo) {}
    public function create(array $d): array { $id=$this->repo->create($d); return $this->repo->findById($id); }
    public function list(int $sid): array { return $this->repo->listBySchool($sid); }
    public function getById(int $id): ?array { return $this->repo->findById($id); }
    public function update(int $id, array $d): void { $this->repo->update($id,$d); }
    public function delete(int $id): void { $this->repo->delete($id); }
    public function getSubjects(int $id): array { return $this->repo->getSubjects($id); }
    public function assignSubject(int $cid, int $sid, bool $compulsory=true): void { $this->repo->assignSubject($cid,$sid,(int)$compulsory); }
    public function removeSubject(int $cid, int $sid): void { $this->repo->removeSubject($cid,$sid); }
    public function getStudentCount(int $id): int { return $this->repo->getStudentCount($id); }
}

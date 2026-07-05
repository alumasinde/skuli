<?php declare(strict_types=1);
namespace Modules\Terms\Services;
use Modules\Terms\Repositories\TermRepository;

final class TermService
{
    public function __construct(private readonly TermRepository $repo) {}
    public function create(array $d): array { $id=$this->repo->create($d); return $this->repo->findById($id); }
    public function listBySchool(int $sid): array { return $this->repo->listBySchool($sid); }
    public function listByYear(int $yid): array { return $this->repo->listByYear($yid); }
    public function getById(int $id): ?array { return $this->repo->findById($id); }
    public function getCurrent(int $sid): ?array { return $this->repo->findCurrent($sid); }
    public function update(int $id, array $d): void { $this->repo->update($id,$d); }
    public function setCurrent(int $id, int $sid): void { $this->repo->setCurrent($id,$sid); }
    public function delete(int $id): void { $this->repo->delete($id); }
}

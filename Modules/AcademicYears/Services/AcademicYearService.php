<?php declare(strict_types=1);
namespace Modules\AcademicYears\Services;
use Modules\AcademicYears\Repositories\AcademicYearRepository;

final class AcademicYearService
{
    public function __construct(private readonly AcademicYearRepository $repo) {}
    public function create(array $d): array { $id=$this->repo->create($d); return $this->repo->findById($id); }
    public function list(int $sid): array { return $this->repo->listBySchool($sid); }
    public function getById(int $id): ?array { return $this->repo->findById($id); }
    public function update(int $id, array $d): void { $this->repo->update($id,$d); }
    public function setCurrent(int $id, int $sid): void { $this->repo->setCurrent($id,$sid); }
    public function delete(int $id): void { $this->repo->delete($id); }
}

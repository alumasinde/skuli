<?php declare(strict_types=1);
namespace Modules\Users\Services;
use Modules\Users\Repositories\UserRepository;

final class UserService
{
    public function __construct(private readonly UserRepository $repo) {}
    public function list(int $schoolId): array { return $this->repo->listBySchool($schoolId); }
    public function getById(int $id): ?array { return $this->repo->findById($id); }
    public function create(array $d): array { $id=$this->repo->create($d); return $this->repo->findById($id); }
    public function update(int $id, array $d): void { $this->repo->update($id,$d); }
    public function activate(int $id): void { $this->repo->setActive($id,true); }
    public function deactivate(int $id): void { $this->repo->setActive($id,false); }
    public function resetPassword(int $id, string $pw): void { $this->repo->resetPassword($id,$pw); }
    public function assignRole(int $userId, int $roleId, int $by): void { $this->repo->assignRole($userId,$roleId,$by); }
    public function removeRole(int $userId, int $roleId): void { $this->repo->removeRole($userId,$roleId); }
    public function listRoles(int $tenantId): array { return $this->repo->listRoles($tenantId); }
}

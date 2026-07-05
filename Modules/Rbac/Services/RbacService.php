<?php declare(strict_types=1);
namespace Modules\Rbac\Services;
use Modules\Rbac\Repositories\RbacRepository;
use Core\Permission;

final class RbacService
{
    public function __construct(private readonly RbacRepository $repo) {}
        public function getRoles(int $tenantId): array { return $this->repo->getRoles($tenantId); }
    public function getPermissions(): array { return $this->repo->getPermissions(); }
    public function getRolePermissions(int $roleId): array { return $this->repo->getRolePermissions($roleId); }
    public function grantPermission(int $roleId, int $permId): void {
        $this->repo->grantPermission($roleId,$permId);
        Permission::invalidate(); // bust the in-process cache
    }
    public function revokePermission(int $roleId, int $permId): void {
        $this->repo->revokePermission($roleId,$permId);
        Permission::invalidate();
    }
}

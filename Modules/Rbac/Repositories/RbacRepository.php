<?php declare(strict_types=1);
namespace Modules\Rbac\Repositories;
use Core\Repository;

final class RbacRepository extends Repository
{
   public function getRoles(int $tenantId): array
    {
        return $this->fetchAll('SELECT * FROM roles WHERE (tenant_id=? OR is_system=1) AND is_active=1 ORDER BY name',
            [$tenantId]);
    }

    public function getPermissions(): array
    {
        return $this->fetchAll('SELECT * FROM permissions WHERE deleted_at IS NULL ORDER BY module,name');
    }

    public function getRolePermissions(int $roleId): array
    {
        return $this->fetchAll('
            SELECT p.* FROM permissions p
            JOIN role_permissions rp ON rp.permission_id=p.id
            WHERE rp.role_id=? AND rp.deleted_at IS NULL
            ORDER BY p.module,p.name
        ', [$roleId]);
    }

    public function grantPermission(int $roleId, int $permId): void
    {
        $this->execute('INSERT IGNORE INTO role_permissions (role_id,permission_id) VALUES (?,?)', [$roleId,$permId]);
    }

    public function revokePermission(int $roleId, int $permId): void
    {
        $this->execute('UPDATE role_permissions SET deleted_at=NOW() WHERE role_id=? AND permission_id=?', [$roleId,$permId]);
    }
}

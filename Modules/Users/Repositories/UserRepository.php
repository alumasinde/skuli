<?php declare(strict_types=1);
namespace Modules\Users\Repositories;
use Core\Repository;

final class UserRepository extends Repository
{
    public function listBySchool(int $schoolId): array
    {
        return $this->fetchAll('
            SELECT u.id,u.first_name,u.last_name,u.email,u.role,u.phone,u.is_active,u.last_login_at,u.created_at,
                   GROUP_CONCAT(r.code ORDER BY r.code) AS role_codes
            FROM users u
            LEFT JOIN user_roles ur ON ur.user_id=u.id AND ur.deleted_at IS NULL
            LEFT JOIN roles r ON r.id=ur.role_id
            WHERE u.school_id=? AND u.deleted_at IS NULL
            GROUP BY u.id ORDER BY u.first_name,u.last_name
        ', [$schoolId]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT id,first_name,last_name,email,role,phone,is_active,created_at FROM users WHERE id=? AND deleted_at IS NULL', [$id]);
    }

    public function create(array $d): int
    {
        $hash = password_hash($d['password'], PASSWORD_BCRYPT);
        return $this->insert('
            INSERT INTO users (tenant_id,school_id,first_name,last_name,email,password_hash,role,phone,is_active)
            VALUES (?,?,?,?,?,?,?,?,1)
        ', [$d['tenant_id'],$d['school_id'],$d['first_name'],$d['last_name'],$d['email'],$hash,$d['role']??'student',$d['phone']??null]);
    }

    public function update(int $id, array $d): void
    {
        $this->execute('UPDATE users SET first_name=?,last_name=?,phone=? WHERE id=?',
            [$d['first_name'],$d['last_name'],$d['phone']??null,$id]);
    }

    public function setActive(int $id, bool $active): void
    {
        $this->execute('UPDATE users SET is_active=? WHERE id=?', [(int)$active,$id]);
    }

    public function resetPassword(int $id, string $newPassword): void
    {
        $hash = password_hash($newPassword, PASSWORD_BCRYPT);
        $this->execute('UPDATE users SET password_hash=? WHERE id=?', [$hash,$id]);
    }

    public function assignRole(int $userId, int $roleId, int $assignedBy): void
    {
        $this->execute('INSERT IGNORE INTO user_roles (user_id,role_id,assigned_by) VALUES (?,?,?)',
            [$userId,$roleId,$assignedBy]);
    }

    public function removeRole(int $userId, int $roleId): void
    {
        $this->execute('UPDATE user_roles SET deleted_at=NOW() WHERE user_id=? AND role_id=?', [$userId,$roleId]);
    }

    public function listRoles(int $tenantId): array
    {
        return $this->fetchAll('SELECT * FROM roles WHERE (tenant_id=? OR is_system=1) AND is_active=1 ORDER BY name',
            [$tenantId]);
    }
}

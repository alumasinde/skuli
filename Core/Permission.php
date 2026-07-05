<?php
declare(strict_types=1);

namespace Core;

use PDO;

/**
 * Permission — pure RBAC permission checking, fully DB-driven.
 *
 * No role or permission name is ever hardcoded in conditional logic
 * anywhere in this class or its callers; the only strings that appear
 * are the permission names passed in BY the caller (e.g. "students.view"),
 * which is the same pattern middleware.RequirePermission(db, "x") used
 * in the Go backend. The actual role->permission mapping lives entirely
 * in role_permissions, queried fresh (with a short in-process cache).
 *
 * IMPORTANT — lesson from the Go build: role_permissions.role_id joins to
 * roles.id, and the JWT must carry roles.code (not roles.name). This class
 * enforces that by joining on r.code, matching what AuthService issues.
 */
final class Permission
{
    private static array $cache = [];
    private const TTL = 300; // 5 minutes, mirrors the Go permcache TTL

    public static function userHas(string $roleCode, string $permission): bool
    {
        $cacheKey = $roleCode;
        $now = time();

        if (!isset(self::$cache[$cacheKey]) || self::$cache[$cacheKey]['expires'] < $now) {
            self::$cache[$cacheKey] = [
                'expires' => $now + self::TTL,
                'perms'   => self::loadPermissionsForRole($roleCode),
            ];
        }

        return in_array($permission, self::$cache[$cacheKey]['perms'], true);
    }

    /** Checks if ANY of the user's roles grant the permission. */
    public static function anyRoleHas(array $roleCodes, string $permission): bool
    {
        foreach ($roleCodes as $code) {
            if (self::userHas($code, $permission)) {
                return true;
            }
        }
        return false;
    }

    private static function loadPermissionsForRole(string $roleCode): array
    {
        $pdo = Database::connection();
        $stmt = $pdo->prepare('
            SELECT p.name
            FROM permissions p
            JOIN role_permissions rp ON rp.permission_id = p.id
            JOIN roles r             ON r.id = rp.role_id
            WHERE r.code = ? AND r.is_active = 1
        ');
        $stmt->execute([$roleCode]);
        return array_column($stmt->fetchAll(), 'name');
    }

    /** Call after any role_permissions change so stale cache isn't served. */
    public static function invalidate(?string $roleCode = null): void
    {
        if ($roleCode === null) {
            self::$cache = [];
        } else {
            unset(self::$cache[$roleCode]);
        }
    }
}

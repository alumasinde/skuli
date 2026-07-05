<?php
declare(strict_types=1);

namespace Modules\Tenants\Repositories;

use Core\Repository;

/**
 * TenantRepository — the tenant is the billing/account boundary; a tenant may
 * eventually own multiple schools (the schema already supports this via
 * schools.tenant_id), though today's UI assumes one school per tenant.
 */
final class TenantRepository extends Repository
{
    public function create(array $d): int
    {
        return $this->insert('
            INSERT INTO tenants (slug, name, domain, plan, status, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ', [
            $d['slug'], $d['name'], $d['domain'] ?? null,
            $d['plan'] ?? 'free', $d['status'] ?? 'active',
            $d['notes'] ?? null, $d['created_by'] ?? null,
        ]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM tenants WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->fetchOne('SELECT * FROM tenants WHERE slug = ? AND deleted_at IS NULL', [$slug]);
    }

    public function slugExists(string $slug): bool
    {
        return $this->fetchOne('SELECT id FROM tenants WHERE slug = ?', [$slug]) !== null;
    }

    public function findBySchoolIdentifier(string $identifier): ?array
{
    $identifier = trim($identifier);
    if ($identifier === '') {
        return null;
    }

    return $this->fetchOne('
        SELECT tn.id, tn.slug, tn.name AS tenant_name, tn.domain, tn.status,
               s.name AS school_name, s.code AS school_code
        FROM tenants tn
        JOIN schools s ON s.tenant_id = tn.id AND s.deleted_at IS NULL
        WHERE tn.deleted_at IS NULL
          AND tn.status IN (\'trial\', \'active\')
          AND (s.code = ? OR s.name LIKE ?)
        ORDER BY (s.code = ?) DESC
        LIMIT 1
    ', [strtoupper($identifier), '%' . $identifier . '%', strtoupper($identifier)]);
}

public function findGlobalRoleIdByCode(string $code): ?int
{
    $row = $this->fetchOne(
        'SELECT id FROM roles WHERE code = ? AND tenant_id IS NULL AND school_id IS NULL AND is_active = 1 LIMIT 1',
        [$code]
    );
    return $row ? (int) $row['id'] : null;
}
 
public function linkUserToRole(int $userId, int $roleId): void
{
    $this->execute(
        'INSERT IGNORE INTO user_roles (user_id, role_id, assigned_at) VALUES (?, ?, NOW())',
        [$userId, $roleId]
    );
}
 
    public function listAll(): array
    {
        // school_count + user_count give the super admin a health snapshot
        // without opening each tenant individually.
        return $this->fetchAll('
            SELECT tn.*,
                   (SELECT COUNT(*) FROM schools s WHERE s.tenant_id = tn.id AND s.deleted_at IS NULL) AS school_count,
                   (SELECT COUNT(*) FROM users u WHERE u.tenant_id = tn.id AND u.deleted_at IS NULL) AS user_count
            FROM tenants tn
            WHERE tn.deleted_at IS NULL
            ORDER BY tn.created_at DESC
        ');
    }

    public function updateStatus(int $id, string $status): void
    {
        $this->execute('UPDATE tenants SET status = ? WHERE id = ?', [$status, $id]);
    }

    public function createSchoolForTenant(int $tenantId, array $school): int
    {
        return $this->insert('
            INSERT INTO schools
                (tenant_id, name, code, address, phone, email, school_type, school_level)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            $tenantId, $school['name'], $school['code'],
            $school['address'] ?? null, $school['phone'] ?? null, $school['email'] ?? null,
            $school['school_type'] ?? 'day', $school['school_level'] ?? 'secondary',
        ]);
    }

    public function createAdminUser(int $tenantId, int $schoolId, array $u): int
    {
        return $this->insert('
            INSERT INTO users (tenant_id, school_id, first_name, last_name, email, password_hash, role, is_active)
            VALUES (?, ?, ?, ?, ?, ?, \'admin\', 1)
        ', [
            $tenantId, $schoolId, $u['first_name'], $u['last_name'], $u['email'], $u['password_hash'],
        ]);
    }

    /** Also seed default settings row so admission numbering works from day one. */
    public function seedSchoolSettings(int $schoolId): void
    {
        $this->execute('
            INSERT IGNORE INTO school_settings (school_id, admission_prefix, admission_year_mode, admission_next, admission_padding)
            VALUES (?, \'SCH\', \'calendar_year\', 1, 4)
        ', [$schoolId]);
    }
}

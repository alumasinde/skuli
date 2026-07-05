<?php
declare(strict_types=1);

namespace Modules\AuditLog\Repositories;

use Core\Repository;

final class AuditLogRepository extends Repository
{
    private const COLS = "
        al.id, al.tenant_id, al.school_id, al.actor_id, al.action, al.entity,
        al.entity_id, al.meta, al.ip_address, al.created_at,
        u.first_name AS actor_first_name, u.last_name AS actor_last_name
    ";

    /** A school admin's activity feed — scoped to their own school only. */
    public function listForSchool(int $schoolId, int $limit = 50, int $offset = 0): array
    {
        return $this->fetchAll("
            SELECT " . self::COLS . "
            FROM audit_logs al
            LEFT JOIN users u ON u.id = al.actor_id
            WHERE al.school_id = ?
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ", [$schoolId, $limit, $offset]);
    }

    public function countForSchool(int $schoolId): int
    {
        return (int) $this->fetchColumn(
            'SELECT COUNT(*) FROM audit_logs WHERE school_id = ?',
            [$schoolId]
        );
    }

    /** Super admin cross-tenant view — every school, every tenant. */
    public function listAll(int $limit = 50, int $offset = 0): array
    {
        return $this->fetchAll("
            SELECT " . self::COLS . ", t.name AS tenant_name
            FROM audit_logs al
            LEFT JOIN users u ON u.id = al.actor_id
            LEFT JOIN tenants t ON t.id = al.tenant_id
            ORDER BY al.created_at DESC
            LIMIT ? OFFSET ?
        ", [$limit, $offset]);
    }

    public function countAll(): int
    {
        return (int) $this->fetchColumn('SELECT COUNT(*) FROM audit_logs', []);
    }

    /** Everything logged against one specific record — e.g. one student's full history. */
    public function listForEntity(string $entity, int $entityId, int $limit = 50): array
    {
        return $this->fetchAll("
            SELECT " . self::COLS . "
            FROM audit_logs al
            LEFT JOIN users u ON u.id = al.actor_id
            WHERE al.entity = ? AND al.entity_id = ?
            ORDER BY al.created_at DESC
            LIMIT ?
        ", [$entity, $entityId, $limit]);
    }
}

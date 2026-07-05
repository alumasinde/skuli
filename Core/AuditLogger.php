<?php
declare(strict_types=1);

namespace Core;
final class AuditLogger
{
    public function __construct(private readonly \PDO $db) {}

    public function log(
        string $action,
        string $entity,
        ?int $entityId = null,
        array $meta = [],
        ?int $tenantId = null,
        ?int $schoolId = null,
        ?int $actorId = null
    ): void {
        try {
            $stmt = $this->db->prepare('
                INSERT INTO audit_logs
                    (tenant_id, school_id, actor_id, action, entity, entity_id, meta, ip_address)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ');
            $stmt->execute([
         
                $tenantId ?? (RequestContext::get('tenant_id') ?: 0),
                $schoolId ?? (RequestContext::get('school_id') ?: 0),
                $actorId  ?? (RequestContext::get('user_id') ?: null),
                $action,
                $entity,
                $entityId,
                $meta === [] ? null : json_encode($meta, JSON_UNESCAPED_SLASHES),
                $this->clientIp(),
            ]);
        } catch (\Throwable $e) {
            error_log('AuditLogger failed: ' . $e->getMessage());
        }
    }

    private function clientIp(): ?string
    {
        return $_SERVER['REMOTE_ADDR'] ?? null;
    }
}

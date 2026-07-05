<?php
declare(strict_types=1);

namespace Core;

use PDO;

/**
 * Repository — base class every module repository extends. Provides the
 * PDO connection and a couple of small query helpers. Deliberately thin —
 * this is NOT an ORM or query builder (the prompt asks for raw PDO with
 * prepared statements, not an abstraction that hides SQL).
 *
 * Multi-tenancy note: tenant/school scoping is NOT automatically injected
 * here. Every query that touches tenant-scoped data must explicitly
 * include WHERE school_id = ? (or join through a table that does). This
 * is intentional — implicit/automatic scoping is exactly the kind of
 * "magic" that caused the Go backend's permission bugs earlier in this
 * project. Explicit is safer than implicit for a security boundary.
 */
abstract class Repository
{
    protected PDO $db;

    public function __construct(?PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    protected function fetchOne(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected function fetchColumn(string $sql, array $params = []): mixed
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }

    protected function execute(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->rowCount();
    }

    protected function insert(string $sql, array $params = []): int
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$this->db->lastInsertId();
    }

    // ── Transactions ─────────────────────────────────────────────────────────
    // Public so a service can wrap several repository calls in one atomic unit
    // (e.g. AdmissionNumberService: lock settings row → read counter →
    // increment → commit). Nested begins are guarded so calling beginTransaction
    // twice on the same connection won't throw.

    public function beginTransaction(): void
    {
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
        }
    }

    public function commit(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->commit();
        }
    }

    public function rollBack(): void
    {
        if ($this->db->inTransaction()) {
            $this->db->rollBack();
        }
    }
}
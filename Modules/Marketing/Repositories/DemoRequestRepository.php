<?php
declare(strict_types=1);

namespace Modules\Marketing\Repositories;

use Core\Repository;

final class DemoRequestRepository extends Repository
{
    public function create(array $d): int
    {
        return $this->insert('
            INSERT INTO demo_requests
                (school_name, contact_name, email, phone, student_count_range, message, source, ip_address)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ', [
            $d['school_name'], $d['contact_name'], $d['email'], $d['phone'] ?? null,
            $d['student_count_range'] ?? null, $d['message'] ?? null,
            $d['source'] ?? null, $d['ip_address'] ?? null,
        ]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM demo_requests WHERE id = ?', [$id]);
    }

    public function listAll(?string $status = null): array
    {
        if ($status !== null) {
            return $this->fetchAll(
                'SELECT * FROM demo_requests WHERE status = ? ORDER BY created_at DESC',
                [$status]
            );
        }
        return $this->fetchAll('SELECT * FROM demo_requests ORDER BY created_at DESC', []);
    }

    public function countNew(): int
    {
        return (int) $this->fetchColumn("SELECT COUNT(*) FROM demo_requests WHERE status = 'new'", []);
    }

    /** Basic spam/duplicate guard: same email submitting again within an hour. */
    public function recentDuplicate(string $email, int $withinMinutes = 60): bool
    {
        return $this->fetchOne('
            SELECT id FROM demo_requests
            WHERE email = ? AND created_at >= (NOW() - INTERVAL ? MINUTE)
            LIMIT 1
        ', [$email, $withinMinutes]) !== null;
    }

    public function updateStatus(int $id, string $status, ?int $reviewedBy = null, ?string $notes = null): void
    {
        $this->execute('
            UPDATE demo_requests
            SET status = ?, reviewed_by = ?, reviewed_at = NOW(), internal_notes = COALESCE(?, internal_notes)
            WHERE id = ?
        ', [$status, $reviewedBy, $notes, $id]);
    }

    public function linkTenant(int $id, int $tenantId): void
    {
        $this->execute('UPDATE demo_requests SET tenant_id = ?, status = \'approved\' WHERE id = ?', [$tenantId, $id]);
    }
}

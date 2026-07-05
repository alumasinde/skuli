<?php
declare(strict_types=1);

namespace Modules\Billing\Repositories;

use Core\Repository;

final class PlanRepository extends Repository
{
    public function listActive(): array
    {
        return $this->fetchAll(
            'SELECT * FROM plans WHERE is_active = 1 AND deleted_at IS NULL ORDER BY sort_order',
            []
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM plans WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    public function findByCode(string $code): ?array
    {
        return $this->fetchOne('SELECT * FROM plans WHERE code = ? AND deleted_at IS NULL', [$code]);
    }
}

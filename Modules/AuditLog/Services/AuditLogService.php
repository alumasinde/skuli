<?php
declare(strict_types=1);

namespace Modules\AuditLog\Services;

use Core\AuditLogFormatter;
use Modules\AuditLog\Repositories\AuditLogRepository;

final class AuditLogService
{
    public function __construct(private readonly AuditLogRepository $repo) {}

    /**
     * @return array{entries: array{sentence:string, action:string, entity:string, created_at:string}[], total:int}
     */
    public function forSchool(int $schoolId, int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $rows   = $this->repo->listForSchool($schoolId, $perPage, $offset);

        return [
            'entries' => $this->decorate($rows),
            'total'   => $this->repo->countForSchool($schoolId),
        ];
    }

    /** Super admin cross-tenant feed. */
    public function forPlatform(int $page = 1, int $perPage = 50): array
    {
        $offset = ($page - 1) * $perPage;
        $rows   = $this->repo->listAll($perPage, $offset);

        return [
            'entries' => $this->decorate($rows),
            'total'   => $this->repo->countAll(),
        ];
    }

    /** Full history for one record, e.g. everything ever logged about student #42. */
    public function forEntity(string $entity, int $entityId, int $limit = 50): array
    {
        return $this->decorate($this->repo->listForEntity($entity, $entityId, $limit));
    }

    /**
     * Attaches the formatted sentence to each row without discarding the raw
     * fields — a view might want the sentence for display AND the raw
     * `action`/`entity` for e.g. an icon or color-coding per action type.
     */
    private function decorate(array $rows): array
    {
        return array_map(static function (array $row) {
            $row['sentence'] = \Core\AuditLogFormatter::format($row);
            return $row;
        }, $rows);
    }
}

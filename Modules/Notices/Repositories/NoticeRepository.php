<?php
declare(strict_types=1);

namespace Modules\Notices\Repositories;

use Core\Repository;

final class NoticeRepository extends Repository
{
    public function create(array $data): int
    {
        return $this->insert('
            INSERT INTO notices (school_id, author_id, title, body, audience, published_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ', [$data['school_id'], $data['author_id'], $data['title'], $data['body'], $data['audience'] ?? 'all']);
    }

    public function list(int $schoolId, string $audience = ''): array
    {
        // Role-scoped: parents see 'all'+'parents', students see 'all'+'students', etc.
        if ($audience !== '' && $audience !== 'all') {
            return $this->fetchAll("
                SELECT * FROM notices
                WHERE school_id = ? AND (audience = 'all' OR audience = ?)
                ORDER BY published_at DESC
            ", [$schoolId, $audience]);
        }
        return $this->fetchAll(
            'SELECT * FROM notices WHERE school_id = ? ORDER BY published_at DESC',
            [$schoolId]
        );
    }

    public function findById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM notices WHERE id = ?', [$id]);
    }

    public function delete(int $id): void
    {
        $this->execute('DELETE FROM notices WHERE id = ?', [$id]);
    }
}

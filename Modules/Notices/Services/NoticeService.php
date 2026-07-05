<?php
declare(strict_types=1);

namespace Modules\Notices\Services;

use Modules\Notices\Repositories\NoticeRepository;

final class NoticeService
{
    public function __construct(private readonly NoticeRepository $repo)
    {
    }

    public function create(array $data, int $authorId): array
    {
        $id = $this->repo->create([...$data, 'author_id' => $authorId]);
        return $this->repo->findById($id);
    }

    public function list(int $schoolId, string $audience = ''): array
    {
        return $this->repo->list($schoolId, $audience);
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function delete(int $id): void
    {
        $this->repo->delete($id);
    }
}

<?php declare(strict_types=1);
namespace Modules\Settings\Services;
use Modules\Settings\Repositories\SettingsRepository;

final class SettingsService
{
    public function __construct(private readonly SettingsRepository $repo) {}
    public function getSchool(int $id): ?array { return $this->repo->getSchool($id); }
    public function updateSchool(int $id, array $d): void { $this->repo->updateSchool($id,$d); }

    public function updateLogo(int $id, string $url): void { $this->repo->updateLogo($id,$url); }

    public function getSchoolSettings(int $schoolId): ?array { return $this->repo->getSchoolSettings($schoolId); }

    public function updateSchoolSettings(int $schoolId, array $d): void { $this->repo->updateSchoolSettings($schoolId,$d); }
}

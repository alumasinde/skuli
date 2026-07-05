<?php
declare(strict_types=1);

namespace Modules\Students\Services;

use Modules\Settings\Repositories\SettingsRepository;
use Throwable;

/**
 * AdmissionNumberService — generates the next sequential admission number for a
 * school, safely under concurrency.
 *
 * Safety model:
 *   BEGIN
 *     SELECT ... FOR UPDATE   (locks the school_settings row)
 *     build number from admission_next
 *     UPDATE admission_next = admission_next + 1
 *   COMMIT
 *
 * The FOR UPDATE lock means if two admissions happen at the same instant, the
 * second one blocks until the first commits, then reads the already-incremented
 * counter — so no two students ever get the same number.
 *
 * Format:  {PREFIX}/{YEAR}/{PADDED_SEQUENCE}   e.g.  SCH/2026/0007
 */
final class AdmissionNumberService
{
    public function __construct(
        private SettingsRepository $settingsRepo
    ) {}

    public function generate(int $schoolId): string
    {
        // Make sure a settings row exists before we try to lock it.
        $this->settingsRepo->ensureSettingsRow($schoolId);

        try {
            $this->settingsRepo->beginTransaction();

            $settings = $this->settingsRepo->lockSettings($schoolId);
            if (!$settings) {
                throw new \RuntimeException('School settings not found for school ' . $schoolId);
            }

            $prefix  = $settings['admission_prefix']  ?? 'SCH';
            $padding = (int)($settings['admission_padding'] ?? 4);
            $next    = (int)($settings['admission_next'] ?? 1);
            $year    = $this->resolveYear($settings['admission_year_mode'] ?? 'calendar_year', $schoolId);

            $admissionNo = sprintf(
                '%s/%s/%s',
                $prefix,
                $year,
                str_pad((string)$next, max(1, $padding), '0', STR_PAD_LEFT)
            );

            $this->settingsRepo->incrementAdmissionNext($schoolId);
            $this->settingsRepo->commit();

            return $admissionNo;
        } catch (Throwable $e) {
            $this->settingsRepo->rollBack();
            throw $e;
        }
    }

    /**
     * Resolve the year segment. 'calendar_year' uses the current calendar year.
     * 'academic_year' also uses the current calendar year here as a safe default;
     * if you track a current academic year elsewhere, swap this to read it.
     */
    private function resolveYear(string $mode, int $schoolId): string
    {
        // Both modes currently resolve to the calendar year. Kept as a seam so
        // 'academic_year' can later pull from the academic_years table without
        // touching the numbering logic.
        return date('Y');
    }
}
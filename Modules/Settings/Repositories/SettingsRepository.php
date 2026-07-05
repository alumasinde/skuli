<?php
declare(strict_types=1);

namespace Modules\Settings\Repositories;

use Core\Repository;

final class SettingsRepository extends Repository
{
    // =========================
    // SCHOOL PROFILE
    // =========================

    public function getSchool(int $schoolId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM schools WHERE id = ?',
            [$schoolId]
        );
    }

    public function updateSchool(int $schoolId, array $d): void
    {
        $this->execute('
            UPDATE schools SET
                name=?,
                address=?,
                phone=?,
                email=?,
                motto=?,
                county=?,
                sub_county=?,
                knec_code=?,
                school_type=?,
                school_level=?,
                principal_name=?,
                mpesa_paybill=?,
                mpesa_account=?,
                website=?
            WHERE id=?
        ', [
            $d['name'] ?? null,
            $d['address'] ?? null,
            $d['phone'] ?? null,
            $d['email'] ?? null,
            $d['motto'] ?? null,
            $d['county'] ?? null,
            $d['sub_county'] ?? null,
            $d['knec_code'] ?? null,
            $d['school_type'] ?? 'day',
            $d['school_level'] ?? 'secondary',
            $d['principal_name'] ?? null,
            $d['mpesa_paybill'] ?? null,
            $d['mpesa_account'] ?? null,
            $d['website'] ?? null,
            $schoolId,
        ]);
    }

    public function updateLogo(int $schoolId, string $logoUrl): void
    {
        $this->execute(
            'UPDATE schools SET logo_url = ? WHERE id = ?',
            [$logoUrl, $schoolId]
        );
    }

    // =========================
    // SCHOOL SETTINGS
    // =========================

    public function getSchoolSettings(int $schoolId): ?array
    {
        return $this->fetchOne(
            'SELECT * FROM school_settings WHERE school_id = ?',
            [$schoolId]
        );
    }

    public function updateSchoolSettings(int $schoolId, array $d): void
    {
        $this->execute('
            UPDATE school_settings
            SET admission_prefix = ?,
                admission_year_mode = ?,
                admission_next = ?,
                admission_padding = ?
            WHERE school_id = ?
        ', [
            $d['admission_prefix'] ?? 'SCH',
            $d['admission_year_mode'] ?? 'academic_year',
            $d['admission_next'] ?? 1,
            $d['admission_padding'] ?? 4,
            $schoolId,
        ]);
    }

    // =========================
    // ADMISSION NUMBERING (TRANSACTIONAL)
    // =========================

    /**
     * Read the settings row with a write lock (FOR UPDATE) so two concurrent
     * enrollments cannot read the same admission_next and collide. MUST be
     * called inside a transaction (see AdmissionNumberService), otherwise the
     * lock is released immediately and provides no protection.
     */
    public function lockSettings(int $schoolId): array
    {
        $row = $this->fetchOne(
            'SELECT * FROM school_settings WHERE school_id = ? FOR UPDATE',
            [$schoolId]
        );

        return $row ?? [];
    }

    public function incrementAdmissionNext(int $schoolId): void
    {
        $this->execute(
            'UPDATE school_settings SET admission_next = admission_next + 1 WHERE school_id = ?',
            [$schoolId]
        );
    }

    /**
     * First-run safety: if a school has no school_settings row yet, create one
     * with defaults so admission numbering (and the settings page) work without
     * a manual insert. Idempotent — INSERT IGNORE relies on a UNIQUE key on
     * school_id (add one if your schema doesn't have it yet).
     */
    public function ensureSettingsRow(int $schoolId): void
    {
        $this->execute(
            'INSERT IGNORE INTO school_settings
                (school_id, admission_prefix, admission_year_mode, admission_next, admission_padding)
             VALUES (?, ?, ?, ?, ?)',
            [$schoolId, 'SCH', 'academic_year', 1, 4]
        );
    }
}
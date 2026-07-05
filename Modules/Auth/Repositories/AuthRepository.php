<?php

declare(strict_types=1);

namespace Modules\Auth\Repositories;

use Core\Repository;

final class AuthRepository extends Repository
{
    public function findUserByEmailAndTenant(
        string $email,
        int $tenantId
    ): ?array {

        $email =
            strtolower(
                trim($email)
            );

        return $this->fetchOne(
            '
            SELECT
                u.id,
                u.tenant_id,
                u.school_id,
                u.first_name,
                u.last_name,
                u.email,
                u.password_hash,
                u.is_active,
                u.deleted_at,
                u.last_login_at

            FROM users u

            WHERE
                LOWER(u.email)=LOWER(?)
                AND u.tenant_id=?
                AND u.is_active=1
                AND u.deleted_at IS NULL

            LIMIT 1
            ',
            [
                $email,
                $tenantId,
            ]
        );
    }

    public function findUserById(
        int $userId
    ): ?array {

        return $this->fetchOne(
            '
            SELECT
                id,
                tenant_id,
                school_id,
                first_name,
                last_name,
                email,
                password_hash,
                is_active

            FROM users

            WHERE
                id=?
                AND is_active=1
                AND deleted_at IS NULL

            LIMIT 1
            ',
            [
                $userId
            ]
        );
    }

    public function updatePassword(
        int $userId,
        string $hash
    ): void {

        $this->execute(
            '
            UPDATE users
            SET
                password_hash=?,
                updated_at=NOW()

            WHERE id=?
            ',
            [
                $hash,
                $userId,
            ]
        );
    }

    /**
     * Returns role codes.
     */
    public function findRoleCodesByUserId(
        int $userId
    ): array {

        $rows =
            $this->fetchAll(
                '
                SELECT
                    DISTINCT r.code

                FROM user_roles ur

                INNER JOIN roles r
                    ON r.id=ur.role_id

                WHERE
                    ur.user_id=?
                    AND r.is_active=1

                ORDER BY r.code
                ',
                [
                    $userId
                ]
            );

        return
            array_values(
                array_unique(
                    array_column(
                        $rows,
                        'code'
                    )
                )
            );
    }

    public function findPermissionsByRoleCodes(
        array $roleCodes
    ): array {

        if (
            empty(
                $roleCodes
            )
        ) {
            return [];
        }

        $placeholders =
            implode(
                ',',
                array_fill(
                    0,
                    count(
                        $roleCodes
                    ),
                    '?'
                )
            );

        $rows =
            $this->fetchAll(
                "
                SELECT DISTINCT
                    p.name

                FROM permissions p

                INNER JOIN role_permissions rp
                    ON rp.permission_id=p.id

                INNER JOIN roles r
                    ON r.id=rp.role_id

                WHERE
                    r.code IN ($placeholders)
                    AND r.is_active=1

                ORDER BY p.name
                ",
                $roleCodes
            );

        return
            array_values(
                array_unique(
                    array_column(
                        $rows,
                        'name'
                    )
                )
            );
    }

    public function getCurrentAcademicYearId(
        int $schoolId
    ): ?int {

        $id =
            $this->fetchColumn(
                '
                SELECT id

                FROM academic_years

                WHERE
                    school_id=?
                    AND is_current=1

                LIMIT 1
                ',
                [
                    $schoolId
                ]
            );

        return
            $id !== false
                ? (int)$id
                : null;
    }

    public function getCurrentTermId(
        int $academicYearId
    ): ?int {

        $id =
            $this->fetchColumn(
                '
                SELECT id

                FROM terms

                WHERE
                    academic_year_id=?
                    AND is_current=1

                LIMIT 1
                ',
                [
                    $academicYearId
                ]
            );

        return
            $id !== false
                ? (int)$id
                : null;
    }

    public function updateLastLogin(
        int $userId
    ): void {

        $this->execute(
            '
            UPDATE users

            SET
                last_login_at=NOW()

            WHERE id=?
            ',
            [
                $userId
            ]
        );
    }

    public function findParentIdByUserId(
        int $userId
    ): ?int {

        $id =
            $this->fetchColumn(
                '
                SELECT id

                FROM parents

                WHERE user_id=?

                LIMIT 1
                ',
                [
                    $userId
                ]
            );

        return
            $id !== false
                ? (int)$id
                : null;
    }

    public function findUserEmail(
        int $userId
    ): ?string {

        $email =
            $this->fetchColumn(
                '
                SELECT email

                FROM users

                WHERE id=?

                LIMIT 1
                ',
                [
                    $userId
                ]
            );

        return
            $email !== false
                ? (string)$email
                : null;
    }
}
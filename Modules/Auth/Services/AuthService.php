<?php

declare(strict_types=1);

namespace Modules\Auth\Services;

use Core\AuditLogger;
use Core\Jwt;
use Core\Logger;
use Modules\Auth\Repositories\AuthRepository;

final class AuthService
{
    private const ACCESS_TTL  = 900;
    private const REFRESH_TTL = 604800;

    public function __construct(
        private readonly AuthRepository $repo,
        private readonly AuditLogger $audit

    ) {}

    public function login(
        string $email,
        string $password,
        int $tenantId
    ): array {

        $email = strtolower(trim($email));

        Logger::info(
            'login attempt',
            [
                'tenant_id' => $tenantId,
                'email' => $email,
            ]
        );

        $user =
            $this->repo
            ->findUserByEmailAndTenant(
                $email,
                $tenantId
            );

        if (!$user) {

            Logger::warn(
                'user not found',
                [
                    'email' => $email,
                    'tenant_id' => $tenantId
                ]
            );

            return $this->failure(
                'Invalid credentials.'
            );
        }

        if (!(bool)$user['is_active']) {

            Logger::warn(
                'inactive account',
                [
                    'user_id' => $user['id']
                ]
            );

            return $this->failure(
                'Account disabled.'
            );
        }

        if (
            empty($user['password_hash']) ||
            !password_verify(
                $password,
                $user['password_hash']
            )
        ) {

            Logger::warn(
                'password verification failed',
                [
                    'user_id' => $user['id']
                ]
            );

            return $this->failure(
                'Invalid credentials.'
            );
        }

        if (
            password_needs_rehash(
                $user['password_hash'],
                PASSWORD_DEFAULT
            )
        ) {
            $this->repo->updatePassword(
                (int)$user['id'],
                password_hash(
                    $password,
                    PASSWORD_DEFAULT
                )
            );
        }

        $roleCodes =
            $this->repo
            ->findRoleCodesByUserId(
                (int)$user['id']
            );

        $permissions =
            $this->repo
            ->findPermissionsByRoleCodes(
                $roleCodes
            );

        $academicYearId = null;
        $termId = null;

        if (!empty($user['school_id'])) {

            $academicYearId =
                $this->repo
                ->getCurrentAcademicYearId(
                    (int)$user['school_id']
                );

            if ($academicYearId !== null) {
                $termId =
                    $this->repo
                    ->getCurrentTermId(
                        $academicYearId
                    );
            }
        }

        $claims = [
            'user_id' => (int)$user['id'],
            'tenant_id' => (int)$user['tenant_id'],
            'school_id' =>
                $user['school_id']
                    ? (int)$user['school_id']
                    : null,

            'roles' => $roleCodes,

            'academic_year_id' =>
                $academicYearId,

            'term_id' =>
                $termId,
        ];

        $accessToken =
            Jwt::issue(
                $claims,
                self::ACCESS_TTL
            );

        $refreshToken =
            Jwt::issue(
                [
                    'user_id' =>
                        (int)$user['id'],

                    'tenant_id' =>
                        (int)$user['tenant_id'],

                    'roles' =>
                        $roleCodes,
                ],
                self::REFRESH_TTL
            );

        $this->repo->updateLastLogin(
            (int)$user['id']
        );

        Logger::info(
            'login successful',
            [
                'user_id' =>
                    $user['id']
            ]
        );

        return [
            'success' => true,

            'data' => [

                'access_token' =>
                    $accessToken,

                'refresh_token' =>
                    $refreshToken,

                'expires_in' =>
                    self::ACCESS_TTL,

                'user' => [

                    'id' =>
                        (int)$user['id'],

                    'first_name' =>
                        $user['first_name'],

                    'last_name' =>
                        $user['last_name'],

                    'name' =>
                        trim(
                            $user['first_name']
                            . ' '
                            . $user['last_name']
                        ),

                    'email' =>
                        $user['email'],

                    'tenant_id' =>
                        (int)$user['tenant_id'],

                    'school_id' =>
                        $user['school_id'],

                    'is_active' =>
                        (bool)$user['is_active'],
                ],

                'roles' =>
                    $roleCodes,

                'permissions' =>
                    $permissions,

                'context' => [

                    'academic_year_id' =>
                        $academicYearId,

                    'term_id' =>
                        $termId,
                ],
            ],
        ];
    }

    public function refresh(
        string $refreshToken
    ): array {

        $claims =
            Jwt::verify(
                $refreshToken
            );

        if (!$claims) {
            return $this->failure(
                'Invalid refresh token.'
            );
        }

        $user =
            $this->repo
            ->findUserById(
                (int)$claims['user_id']
            );

        if (
            !$user ||
            !(bool)$user['is_active']
        ) {
            return $this->failure(
                'Account no longer active.'
            );
        }

        return [
            'success' => true,

            'data' => [

                'access_token' =>
                    Jwt::issue(
                        $claims,
                        self::ACCESS_TTL
                    ),

                'expires_in' =>
                    self::ACCESS_TTL,
            ],
        ];
    }

    private function failure(
        string $message
    ): array {

        return [
            'success' => false,
            'error' => $message,
            'data' => null,
        ];
    }
}
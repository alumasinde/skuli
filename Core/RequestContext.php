<?php

declare(strict_types=1);

namespace Core;

/**
 * RequestContext
 *
 * Holds authenticated request data for the current request.
 * Uses explicitly set context first, then session fallback.
 */
final class RequestContext
{
    private static array $data = [];

    public static function set(array $data): void
    {
        self::$data = $data;
    }

    public static function get(
        string $key,
        mixed $default = null
    ): mixed {
        if (array_key_exists($key, self::$data)) {
            return self::$data[$key];
        }

        if (
            isset($_SESSION) &&
            array_key_exists($key, $_SESSION)
        ) {
            return $_SESSION[$key];
        }

        if (
            isset($_SESSION['user']) &&
            is_array($_SESSION['user']) &&
            array_key_exists($key, $_SESSION['user'])
        ) {
            return $_SESSION['user'][$key];
        }

        return $default;
    }

    public static function userId(): ?int
    {
        $value = self::get('user_id')
            ?? self::get('id');

        return $value !== null
            ? (int) $value
            : null;
    }

    public static function tenantId(): ?int
    {
        $value = self::get('tenant_id');

        return $value !== null
            ? (int) $value
            : null;
    }

    public static function schoolId(): ?int
    {
        $value = self::get('school_id');

        return $value !== null
            ? (int) $value
            : null;
    }

    /**
     * @return string[]
     */
    public static function roles(): array
    {
        $roles = self::get('roles', []);

        return is_array($roles)
            ? $roles
            : [];
    }

    public static function hasRole(
        string $code
    ): bool {
        return in_array(
            $code,
            self::roles(),
            true
        );
    }

    public static function academicYearId(): ?int
    {
        $value = self::get('academic_year_id');

        return $value !== null
            ? (int) $value
            : null;
    }

    public static function termId(): ?int
    {
        $value = self::get('term_id');

        return $value !== null
            ? (int) $value
            : null;
    }

    public static function user(): array
    {
        $user = self::get('user', []);

        return is_array($user)
            ? $user
            : [];
    }

    public static function isAuthenticated(): bool
    {
        return self::userId() !== null;
    }

    public static function clear(): void
    {
        self::$data = [];
    }
}

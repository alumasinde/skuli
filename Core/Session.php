<?php
declare(strict_types=1);

namespace Core;

use DateTime;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            ini_set('session.cookie_httponly', '1');
            ini_set('session.cookie_samesite', 'Lax');
            if (Env::get('APP_ENV') === 'production') {
                ini_set('session.cookie_secure', '1');
            }
            // Server-side session lifetime should be at least as long as the
            // idle timeout below, or PHP's garbage collector could reap a
            // session before your own idle check ever gets a chance to.
            ini_set('session.gc_maxlifetime', (string) self::idleTimeoutSeconds());
            session_name('sms_sess');
            session_start();
        }
    }

    public static function can(string $permission): bool
    {
        if (self::hasRole('superadmin')) {
            return true;
        }
        $permissions = self::get('permissions', []);
        return in_array($permission, $permissions, true);
    }

    public static function hasRole(string $role): bool
    {
        $roles = self::get('roles', []);
        return in_array($role, $roles, true);
    }

    public static function role(): string
    {
        $roles = self::get('roles', []);
        return $roles[0] ?? '';
    }

    public static function roles(): array
    {
        return self::get('roles', []);
    }

    public static function set(string $key, mixed $value): void
    {
        self::start();
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        self::start();
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        self::start();
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        self::start();
        unset($_SESSION[$key]);
    }
    public static function destroy(): void
    {
        self::start();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000, // any time in the past — tells the browser to delete it immediately
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_unset();
        session_destroy();
    }

    public static function isLoggedIn(): bool
    {
        return self::has('access_token') && self::has('user');
    }

    public static function user(): ?array
    {
        return self::get('user');
    }

    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            self::set('_flash_' . $key, $value);
            return null;
        }
        $val = self::get('_flash_' . $key);
        self::remove('_flash_' . $key);
        return $val;
    }

    public static function formatDate(?string $date, string $format = 'd M Y'): string
    {
        if (empty($date)) {
            return '—';
        }
        try {
            return (new DateTime($date))->format($format);
        } catch (\Exception $e) {
            return '—';
        }
    }

    // ── Idle timeout — "log out if not in use" ──────────────────────────────

    /** How many seconds of inactivity before auto-logout. Defaults to 30 min. */
    public static function idleTimeoutSeconds(): int
    {
        return Env::int('SESSION_IDLE_TIMEOUT', 1800);
    }

    /** Call on every authenticated request that should count as "activity". */
    public static function touchActivity(): void
    {
        self::set('_last_activity', time());
    }

    /**
     * True if the session has been idle longer than the configured timeout.
     * Does NOT destroy the session itself — the caller (AuthenticateWeb)
     * decides what to do, so this stays a pure check.
     */
    public static function isIdleTimedOut(): bool
    {
        $last = self::get('_last_activity');
        if ($last === null) {
            return false; // no prior activity recorded yet — first request, not a timeout
        }
        return (time() - (int) $last) > self::idleTimeoutSeconds();
    }

    // ── CSRF protection ──────────────────────────────────────────────────────

    public static function csrfToken(): string
    {
        if (!self::has('_csrf_token')) {
            self::set('_csrf_token', bin2hex(random_bytes(32)));
        }
        return self::get('_csrf_token');
    }

    public static function verifyCsrf(string $token): bool
    {
        $stored = self::get('_csrf_token', '');
        return $stored !== '' && hash_equals($stored, $token);
    }
}

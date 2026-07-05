<?php
declare(strict_types=1);

/**
 * Global helper functions. Each is a thin wrapper over a Core class —
 * defined once here, used everywhere, no duplicated logic anywhere else
 * in the codebase.
 */

use Core\Env;
use Core\Session;
use Core\Response;

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    /** Reads from /config/*.php files: config('app.name') -> config/app.php['name'] */
    function config(string $key, mixed $default = null): mixed
    {
        static $cache = [];
        [$file, $field] = array_pad(explode('.', $key, 2), 2, null);

        if (!isset($cache[$file])) {
            $path = dirname(__DIR__) . "/config/{$file}.php";
            $cache[$file] = file_exists($path) ? require $path : [];
        }
        return $field !== null ? ($cache[$file][$field] ?? $default) : ($cache[$file] ?: $default);
    }
}

if (!function_exists('auth')) {
    /** Returns the current authenticated user array from session, or null. */
    function auth(): ?array
    {
        return Session::user();
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return Session::csrfToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        $token = htmlspecialchars(csrf_token(), ENT_QUOTES);
        return "<input type=\"hidden\" name=\"_csrf\" value=\"{$token}\">";
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/assets/' . ltrim($path, '/');
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, ?string $message = null, string $type = 'success'): never
    {
        if ($message !== null) {
            Session::flash($type, $message);
        }
        header("Location: {$path}");
        exit;
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): never
    {
        Response::success($data, '', $status);
        exit;
    }
}

if (!function_exists('view')) {
    /**
     * Renders a view file with $data extracted into scope, wrapped in the
     * shared layout. Mirrors Controller::view() exactly so existing
     * controller code (40+ files from the prior frontend) needs zero
     * changes.
     */
    function view(string $name, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $viewFile = dirname(__DIR__) . "/Modules/{$name}.view.php";
        if (!file_exists($viewFile)) {
            // Fallback: legacy flat views/ directory layout
            $viewFile = dirname(__DIR__) . "/views/{$name}.php";
        }
        $title   = $data['title']   ?? '';
        $appName = config('app.name', 'SchoolMS');
        require dirname(__DIR__) . '/views/layout.php';
    }
}

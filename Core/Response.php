<?php
declare(strict_types=1);

namespace Core;

use Core\Http;

final class Response
{
    public static function success(mixed $data, string $message = '', Http $status = Http::OK): void
    {
        self::json(['success' => true, 'data' => $data, 'message' => $message], $status);
    }

    public static function created(mixed $data, string $message = ''): void
    {
        self::success($data, $message, Http::CREATED);
    }

    public static function paginated(array $data, array $meta): void
    {
        self::json(['success' => true, 'data' => $data, 'meta' => $meta], Http::OK);
    }

    public static function error(string $message, Http $status = Http::BAD_REQUEST): void
    {
        self::json(['success' => false, 'error' => $message, 'data' => null], $status);
    }

    public static function htmlError(string $message, Http $status = Http::BAD_REQUEST): void
    {
        http_response_code($status->value);
        header('Content-Type: text/html; charset=utf-8');
        echo "<h1>Error</h1><p>" . htmlspecialchars($message) . "</p>";
        exit;
    }

    public static function badRequest(string $message): void
    {
        self::error($message, Http::BAD_REQUEST);
    }

    public static function unauthorized(string $message = 'unauthorized'): void
    {
        self::error($message, Http::UNAUTHORIZED);
    }

    public static function forbidden(string $message = 'forbidden'): void
    {
        self::error($message, Http::FORBIDDEN);
    }

    public static function notFound(string $message = 'not found'): void
    {
        self::error($message, Http::NOT_FOUND);
    }

    public static function serverError(\Throwable $e): void
    {
        Logger::error('internal server error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
        $message = Env::get('APP_ENV') === 'production'
            ? 'internal server error'
            : $e->getMessage();
        self::error($message, Http::SERVER_ERROR);
    }

    private static function json(array $payload, Http $status): void
    {
        http_response_code($status->value);
        header('Content-Type: application/json');
        echo json_encode($payload);
    }
}

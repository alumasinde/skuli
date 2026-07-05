<?php
declare(strict_types=1);

namespace Core;

/**
 * UrlBuilder — builds a tenant's absolute login URL from its `domain` column.
 *
 * Replaces the earlier approach of two separate env vars (APP_FORCE_HTTPS,
 * APP_LOCAL_PORT) that a developer had to remember to set correctly for
 * local dev. That's exactly what just broke: APP_FORCE_HTTPS defaulted to
 * true, so every generated login URL was https:// even on a plain-HTTP
 * `php -S` dev server, which can't speak TLS at all — hence the endless
 * "Unsupported SSL request" flood in the log.
 *
 * Instead, this mirrors whatever scheme and port the CURRENT request
 * actually arrived on:
 *   - Local dev (http://localhost:8000/...)  -> generates http://...:8000/login
 *   - Production behind a real reverse proxy on 443 -> generates
 *     https://domain/login with no port, automatically, since $_SERVER
 *     won't have a port in HTTP_HOST and HTTPS will be set.
 * No env var to remember, no config that can drift out of sync with reality.
 */
final class UrlBuilder
{
    public static function tenantLoginUrl(string $domain): string
    {
        return self::currentScheme() . '://' . $domain . self::currentPort() . '/login';
    }

    private static function currentScheme(): string
    {
        $https = $_SERVER['HTTPS'] ?? '';
        return ($https !== '' && strtolower($https) !== 'off') ? 'https' : 'http';
    }

    private static function currentPort(): string
    {
        $host = $_SERVER['HTTP_HOST'] ?? '';
        if (str_contains($host, ':')) {
            return ':' . explode(':', $host, 2)[1];
        }
        return '';
    }
}
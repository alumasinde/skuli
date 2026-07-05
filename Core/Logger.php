<?php
declare(strict_types=1);

namespace Core;

/**
 * Logger — minimal structured file logger. Writes JSON lines so logs are
 * machine-parseable later if you move to a real log aggregator.
 */
final class Logger
{
    private static function path(string $channel): string
    {
        $dir = dirname(__DIR__) . '/storage/logs';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        return $dir . "/{$channel}-" . date('Y-m-d') . '.log';
    }

    private static function write(string $channel, string $level, string $message, array $context = []): void
    {
        $entry = [
            'timestamp' => date('c'),
            'level'     => $level,
            'message'   => $message,
            'context'   => $context,
        ];
        @file_put_contents(self::path($channel), json_encode($entry) . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('app', 'INFO', $message, $context);
    }

    public static function warn(string $message, array $context = []): void
    {
        self::write('app', 'WARN', $message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::write('app', 'ERROR', $message, $context);
    }

    /** Separate channel for permission/auth decisions — useful for security review. */
    public static function audit(string $message, array $context = []): void
    {
        self::write('audit', 'AUDIT', $message, $context);
    }
}

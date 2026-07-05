<?php
declare(strict_types=1);

namespace Core;

final class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug): void
    {
        self::$debug = $debug;

        // Surface every PHP error/warning/notice as an exception so it goes
        // through the same handling path instead of being echoed inline.
        set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false; // respect @-suppressed errors / error_reporting level
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });

        set_exception_handler([self::class, 'handle']);

        // Catches fatal errors that bypass the exception handler entirely
        // (e.g. memory exhaustion) so we still return a clean response.
        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::handle(new \ErrorException(
                    $error['message'], 0, $error['type'], $error['file'], $error['line']
                ));
            }
        });
    }

    public static function handle(\Throwable $e): void
    {
        // Always log full detail server-side, regardless of debug mode.
        error_log(sprintf(
            "[%s] %s in %s:%d\n%s",
            (new \DateTime())->format('Y-m-d H:i:s'),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        ));

        if (!headers_sent()) {
            http_response_code(500);
        }

        $wantsJson = self::wantsJson();

        if (self::$debug) {
            self::renderDebug($e, $wantsJson);
            return;
        }

        self::renderProduction($wantsJson);
    }

    private static function wantsJson(): bool
    {
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        $xhr    = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
        $path   = $_SERVER['REQUEST_URI'] ?? '';

        return str_contains($accept, 'application/json')
            || strcasecmp($xhr, 'XMLHttpRequest') === 0
            || str_starts_with((string) parse_url($path, PHP_URL_PATH), '/api/');
    }

    private static function renderProduction(bool $json): void
    {
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => 'Something went wrong. Please try again, or contact support if this persists.',
            ]);
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<!doctype html><html><head><meta charset="utf-8">'
           . '<title>Something went wrong</title></head><body '
           . 'style="font-family:system-ui,sans-serif;max-width:560px;margin:80px auto;text-align:center;color:#333;">'
           . '<h1 style="font-size:1.5rem;">Something went wrong</h1>'
           . '<p style="color:#666;">We hit an unexpected error. It has been logged and we will look into it. '
           . 'Please go back and try again.</p>'
           . '</body></html>';
    }

    private static function renderDebug(\Throwable $e, bool $json): void
    {
        if ($json) {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error'   => $e->getMessage(),
                'file'    => $e->getFile(),
                'line'    => $e->getLine(),
                'trace'   => explode("\n", $e->getTraceAsString()),
            ], JSON_PRETTY_PRINT);
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        printf(
            '<!doctype html><html><head><meta charset="utf-8"><title>%s</title></head>'
          . '<body style="font-family:ui-monospace,monospace;background:#1e1e1e;color:#d4d4d4;padding:2rem;">'
          . '<h1 style="color:#f14c4c;">%s</h1>'
          . '<p><strong>%s:%d</strong></p>'
          . '<pre style="white-space:pre-wrap;background:#252526;padding:1rem;border-radius:6px;">%s</pre>'
          . '</body></html>',
            htmlspecialchars(get_class($e)),
            htmlspecialchars($e->getMessage()),
            htmlspecialchars($e->getFile()),
            $e->getLine(),
            htmlspecialchars($e->getTraceAsString())
        );
    }
}

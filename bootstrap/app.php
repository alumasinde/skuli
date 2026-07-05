<?php

declare(strict_types=1);

use Core\Env;
use Core\Logger;

require dirname(__DIR__) . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Environment
|--------------------------------------------------------------------------
*/
Env::load(dirname(__DIR__) . '/.env');

/*
|--------------------------------------------------------------------------
| Error handling
|--------------------------------------------------------------------------
*/
$isDebug = Env::get('APP_DEBUG', 'false') === 'true';

error_reporting(E_ALL);
ini_set('display_errors', $isDebug ? '1' : '0');

set_exception_handler(
    function (\Throwable $e) use ($isDebug): void {
        Logger::error('uncaught exception', [
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString(),
        ]);

        if (!headers_sent()) {
            http_response_code(500);

            header('Content-Type: application/json');

            echo json_encode([
                'success' => false,
                'error' => $isDebug
                    ? $e->getMessage()
                    : 'Internal server error.',
                'data' => null,
            ]);

            return;
        }

        echo '<div style="padding:16px">';
        echo $isDebug
            ? htmlspecialchars($e->getMessage())
            : 'Internal server error.';
        echo '</div>';
    }
);

set_error_handler(
    function (
        int $severity,
        string $message,
        string $file,
        int $line
    ): bool {
        if (!(error_reporting() & $severity)) {
            return false;
        }

        throw new ErrorException(
            $message,
            0,
            $severity,
            $file,
            $line
        );
    }
);

/*
|--------------------------------------------------------------------------
| Build and return container
|--------------------------------------------------------------------------
*/
return require __DIR__ . '/container.php';
<?php
declare(strict_types=1);

namespace Core\Middleware;

use Core\Contracts\Middleware;
use Core\Session;

/**
 * VerifyCsrf — enforces CSRF token on all state-changing web requests.
 * Applied to all POST/PUT/DELETE in routes/web.php. Safe methods (GET,
 * HEAD) pass through. API routes (/api/v1/*) are excluded from this
 * middleware since they use Bearer JWT auth instead.
 */
final class VerifyCsrf implements Middleware
{
    public function handle(array &$context, callable $next): void
    {
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');

        if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) {
            $next();
            return;
        }

        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if (!Session::verifyCsrf($token)) {
            http_response_code(419);
            header('Content-Type: text/html');
            echo '<!DOCTYPE html><html><body><h2>Session expired.</h2>
                  <p>Please <a href="javascript:history.back()">go back</a> and try again.</p></body></html>';
            return;
        }

        $next();
    }
}

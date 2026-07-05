<?php
declare(strict_types=1);

namespace Core\Middleware;

use Core\Contracts\Middleware;
use Core\Session;

final class AuthenticateWeb implements Middleware
{
    public function handle(array &$context, callable $next): void
    {
        // Never cache an authenticated page — closes the "back button shows
        // me still logged in" gap that a fixed cookie alone doesn't cover.
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');

        if (!Session::isLoggedIn()) {
            $intendedUrl = $_SERVER['REQUEST_URI'] ?? '/dashboard';
            Session::set('intended_url', $intendedUrl);
            header('Location: /login');
            exit;
        }

        if (Session::isIdleTimedOut()) {
            Session::destroy();
            Session::flash('error', 'You were signed out after a period of inactivity.');
            header('Location: /login');
            exit;
        }

        Session::touchActivity();
        $next();
    }
}

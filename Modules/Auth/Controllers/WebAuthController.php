<?php

declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Core\Session;
use Core\RateLimiter;
use Core\UrlBuilder;
use Core\TenantResolver;
use Core\WebController;

use Modules\Auth\Services\AuthService;
use Modules\Tenants\Repositories\TenantRepository;

final class WebAuthController extends WebController
{
    private AuthService $service;
    private TenantRepository $tenants;

    public function __construct(AuthService $service, TenantRepository $tenants)
    {
        $this->service = $service;
        $this->tenants = $tenants;
    }
    public function showLogin(array $params): void
    {
        if (Session::isLoggedIn()) {
            redirect('/dashboard');
            return;
        }

        $tenant = TenantResolver::resolve();

        extract([
            'title'   => 'Sign In — SchoolMS',
            'appName' => config('app.name', 'SchoolMS'),
        ]);

        $viewFile = $tenant === null
            ? dirname(__DIR__, 3) . '/views/auth/find_school.php'
            : dirname(__DIR__, 3) . '/views/auth/login.php';

        require $viewFile;
    }

    public function findSchool(array $params): void
    {
        $identifier = trim($_POST['school'] ?? '');

        if ($identifier === '') {
            Session::flash('error', 'Please enter your school name or code.');
            redirect('/login');
            return;
        }

        $tenant = $this->tenants->findBySchoolIdentifier($identifier);

        if (!$tenant || empty($tenant['domain'])) {
            Session::flash(
                'error',
                "We couldn't find a school matching \"{$identifier}\". Check the spelling, or "
                . '<a href="/demo">request a demo</a> if you\'re not set up yet.'
            );
            redirect('/login');
            return;
        }

        header('Location: ' . UrlBuilder::tenantLoginUrl($tenant['domain']));
        exit;
    }

    public function login(array $params): void
    {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if ($email === '' || $password === '') {
            Session::flash('error', 'Email and password are required.');
            redirect('/login');
            return;
        }

        $tenant = TenantResolver::resolve();

        if (!$tenant) {
          
            Session::flash('error', 'Please find your school first.');
            redirect('/login');
            return;
        }

        $result = $this->service->login($email, $password, (int) $tenant['id']);
        (new RateLimiter())->clear('auth:' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));

        if (!($result['success'] ?? false)) {
            Session::flash('error', $result['error'] ?? 'Invalid credentials.');
            redirect('/login');
            return;
        }

        $payload = $result['data'];

        session_regenerate_id(true);

        Session::set('access_token', $payload['access_token']);
        Session::set('refresh_token', $payload['refresh_token']);
        Session::set('user', $payload['user']);
        Session::set('school_id', $payload['user']['school_id'] ?? null);
        Session::set('tenant_id', $payload['user']['tenant_id'] ?? $tenant['id']);
        Session::set('roles', $payload['roles'] ?? []);
        Session::set('permissions', $payload['permissions'] ?? []);

        $intended = Session::get('intended_url', '/dashboard');
        Session::remove('intended_url');

        redirect($intended);
    }

    public function logout(array $params): void
    {
        Session::destroy();
        redirect('/login', 'You have been signed out.');
    }
}

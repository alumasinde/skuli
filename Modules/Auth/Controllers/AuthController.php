<?php
declare(strict_types=1);

namespace Modules\Auth\Controllers;

use Core\Response;
use Core\TenantResolver;
use Core\Logger;
use Modules\Auth\Services\AuthService;
final class AuthController
{
    private AuthService $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    public function login(array $params): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $email    = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            Response::badRequest('Email and password are required.');
            return;
        }

        $tenant = TenantResolver::resolve();
        if (!$tenant) {
            Logger::error('login failed: no tenant resolved', ['host' => $_SERVER['HTTP_HOST'] ?? '']);
            Response::badRequest('Unable to resolve school for this domain.');
            return;
        }

        Logger::info('login request received', [
            'method'      => 'POST',
            'path'        => '/api/v1/auth/login',
            'tenant_id'   => $tenant['id'],
            'host'        => $_SERVER['HTTP_HOST'] ?? '',
        ]);

        $result = $this->service->login($email, $password, (int)$tenant['id']);

        if (!$result['success']) {
            Response::error($result['error'], 401);
            return;
        }

        Response::success($result['data']);
    }

    public function refresh(array $params): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $refreshToken = $body['refresh_token'] ?? '';

        if ($refreshToken === '') {
            Response::badRequest('refresh_token is required.');
            return;
        }

        $result = $this->service->refresh($refreshToken);
        if (!$result['success']) {
            Response::error($result['error'], 401);
            return;
        }
        Response::success($result['data']);
    }

    public function logout(array $params): void
    {
    
        Response::success(null, 'logged out');
    }
}

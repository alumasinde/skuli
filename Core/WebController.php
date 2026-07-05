<?php
declare(strict_types=1);

namespace Core;

/**
 * WebController — base class for all server-rendered page controllers.
 * API controllers (Auth, Students, etc.) are final and don't extend this;
 * they call Response:: directly. This class is for the web UI layer only.
 */
abstract class WebController
{

 public function __construct()
    {
        $this->hydrateRequestContext();
    }
  private function hydrateRequestContext(): void
    {
        if (!Session::isLoggedIn()) {
            return;
        }
 
        $user = Session::get('user', []);
 
        RequestContext::set([
            'user_id'   => $user['id'] ?? 0,
            'tenant_id' => Session::get('tenant_id', 0),
            'school_id' => Session::get('school_id'),
            'roles'     => Session::get('roles', []),
        ]);
    }
    protected function view(string $name, array $data = []): void
    {
        if (!Session::isLoggedIn()) {
            redirect('/login');
        }
        view($name, $data);
    }

    protected function redirect(string $path, ?string $message = null, string $type = 'success'): never
    {
        redirect($path, $message, $type);
    }

    protected function requirePermission(string $permission): void
    {
        if (!Session::can($permission)) {
            http_response_code(403);
            view('errors/403', ['title' => 'Access Denied']);
            exit;
        }
    }

    protected function requireRole(string ...$roles): void
    {
        foreach ($roles as $role) {
            if (Session::hasRole($role)) return;
        }
        http_response_code(403);
        view('errors/403', ['title' => 'Access Denied']);
        exit;
    }

    protected function api(string $method, string $path, array $data = []): array
    {
        $baseUrl = rtrim(env('APP_URL', 'http://localhost'), '/');
        $url     = $baseUrl . '/api/v1' . $path;
        $token   = Session::get('access_token', '');

        $opts = [
            'http' => [
                'method'  => strtoupper($method),
                'header'  => implode("\r\n", [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    "Authorization: Bearer {$token}",
                ]),
                'ignore_errors' => true,
                'timeout' => 10,
            ],
        ];

        if (!empty($data) && in_array(strtoupper($method), ['POST', 'PUT', 'PATCH'], true)) {
            $opts['http']['content'] = json_encode($data);
        } elseif (!empty($data) && strtoupper($method) === 'GET') {
            $url .= '?' . http_build_query($data);
        }

        $ctx      = stream_context_create($opts);
        $body     = @file_get_contents($url, false, $ctx);
        $response = json_decode($body ?: '{}', true);

        return is_array($response) ? $response : ['success' => false, 'data' => null, 'error' => 'API error'];
    }

    protected function schoolId(): ?int
    {
        return Session::get('school_id');
    }

    protected function currentUser(): ?array
    {
        return Session::user();
    }
}

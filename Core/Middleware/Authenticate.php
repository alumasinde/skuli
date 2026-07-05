<?php
declare(strict_types=1);

namespace Core\Middleware;

use Core\Contracts\Middleware;
use Core\Jwt;
use Core\Response;
use Core\RequestContext;

/**
 * Authenticate — verifies the Bearer JWT and populates RequestContext
 * with the resolved user_id, tenant_id, school_id, roles. Direct
 * equivalent of the Go Authenticate middleware.
 */
final class Authenticate implements Middleware
{
    public function handle(array &$context, callable $next): void
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if ($header === '' || !str_starts_with($header, 'Bearer ')) {
            Response::unauthorized('missing or invalid authorization header');
            return;
        }

        $token  = substr($header, 7);
        $claims = Jwt::verify($token);

        if ($claims === null) {
            Response::unauthorized('invalid or expired token');
            return;
        }

        RequestContext::set([
            'user_id'          => (int)($claims['user_id'] ?? 0),
            'tenant_id'        => (int)($claims['tenant_id'] ?? 0),
            'school_id'        => isset($claims['school_id']) ? (int)$claims['school_id'] : null,
            'roles'            => $claims['roles'] ?? [],
            'academic_year_id' => isset($claims['academic_year_id']) ? (int)$claims['academic_year_id'] : null,
            'term_id'          => isset($claims['term_id']) ? (int)$claims['term_id'] : null,
        ]);

        $next();
    }
}

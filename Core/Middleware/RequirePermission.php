<?php
declare(strict_types=1);

namespace Core\Middleware;

use Core\Contracts\Middleware;
use Core\Permission;
use Core\RequestContext;
use Core\Response;
use Core\Logger;

/**
 * RequirePermission — gate factory. Usage in routes:
 *
 *   $router->get('/students', [StudentController::class, 'index'],
 *       [RequirePermission::for('students.view')]);
 *
 * No permission name is ever decided by role inside this class — the
 * permission string is supplied by the route definition, and the
 * role->permission mapping is looked up entirely from the DB via
 * Permission::anyRoleHas(). This mirrors middleware.RequirePermission(db,
 * "x") from the Go backend exactly, including the lesson learned there:
 * roles in the JWT are codes ("admin"), and the DB join must use r.code.
 */
final class RequirePermission implements Middleware
{
    public function __construct(private readonly string $permission)
    {
    }

    public static function for(string $permission): self
    {
        return new self($permission);
    }

    public function handle(array &$context, callable $next): void
    {
        $roles = RequestContext::roles();

        if (!Permission::anyRoleHas($roles, $this->permission)) {
            Logger::audit('permission denied', [
                'user_id'    => RequestContext::userId(),
                'roles'      => $roles,
                'permission' => $this->permission,
            ]);
            Response::forbidden('you do not have permission to perform this action');
            return;
        }

        $next();
    }
}

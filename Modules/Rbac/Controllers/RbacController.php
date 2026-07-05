<?php declare(strict_types=1);
namespace Modules\Rbac\Controllers;
use Core\RequestContext;use Core\Response;
use Modules\Rbac\Repositories\RbacRepository;
use Modules\Rbac\Services\RbacService;

final class RbacController
{
    private RbacService $svc;
    public function __construct() { $this->svc=new RbacService(new RbacRepository()); }

    public function getRoles(array $p): void {
        Response::success($this->svc->getRoles(RequestContext::tenantId()));
    }

    public function getPermissions(array $p): void {
        Response::success($this->svc->getPermissions());
    }

    public function getRolePermissions(array $p): void {
        Response::success($this->svc->getRolePermissions((int)($p['roleId']??0)));
    }

    public function grantPermission(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        $this->svc->grantPermission((int)($p['roleId']??0),(int)($b['permission_id']??0));
        Response::success(null,'permission granted');
    }

    public function revokePermission(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        $this->svc->revokePermission((int)($p['roleId']??0),(int)($b['permission_id']??0));
        Response::success(null,'permission revoked');
    }
}

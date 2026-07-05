<?php declare(strict_types=1);
namespace Modules\Users\Controllers;
use Core\RequestContext;use Core\Response;
use Modules\Users\Repositories\UserRepository;
use Modules\Users\Services\UserService;

final class UserController
{
    private UserService $svc;
    public function __construct() { $this->svc=new UserService(new UserRepository()); }

    public function index(array $p): void { $sid=RequestContext::schoolId(); Response::success($sid ? $this->svc->list($sid) : []); }
    public function show(array $p): void { $u=$this->svc->getById((int)($p['id']??0)); $u ? Response::success($u) : Response::notFound('user not found'); }
    public function create(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $b=json_decode(file_get_contents('php://input'),true)??[];
        $b['school_id']=$sid; $b['tenant_id']=RequestContext::tenantId();
        if(empty($b['email'])||empty($b['password'])){Response::badRequest('email and password required');return;}
        try { Response::created($this->svc->create($b),'user created'); } catch(\Throwable $e){ Response::serverError($e); }
    }
    public function update(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        try { $this->svc->update((int)($p['id']??0),$b); Response::success(null,'updated'); } catch(\Throwable $e){ Response::serverError($e); }
    }
    public function activate(array $p): void { $this->svc->activate((int)($p['id']??0)); Response::success(null,'user activated'); }
    public function deactivate(array $p): void { $this->svc->deactivate((int)($p['id']??0)); Response::success(null,'user deactivated'); }
    public function resetPassword(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        if(empty($b['password'])){Response::badRequest('password required');return;}
        $this->svc->resetPassword((int)($p['id']??0),$b['password']); Response::success(null,'password reset');
    }
    public function assignRole(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        $this->svc->assignRole((int)($p['id']??0),(int)($b['role_id']??0),RequestContext::userId());
        Response::success(null,'role assigned');
    }
    public function removeRole(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        $this->svc->removeRole((int)($p['id']??0),(int)($b['role_id']??0));
        Response::success(null,'role removed');
    }
    public function roles(array $p): void {
        Response::success($this->svc->listRoles(RequestContext::tenantId()));
    }
}

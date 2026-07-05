<?php declare(strict_types=1);
namespace Modules\Terms\Controllers;
use Core\RequestContext;use Core\Response;
use Modules\Terms\Repositories\TermRepository;
use Modules\Terms\Services\TermService;

final class TermController
{
    private TermService $svc;
    public function __construct() { $this->svc=new TermService(new TermRepository()); }

    public function index(array $p): void { $sid=RequestContext::schoolId(); Response::success($sid ? $this->svc->listBySchool($sid) : []); }
    public function current(array $p): void {
        $sid=RequestContext::schoolId();
        $t=$sid ? $this->svc->getCurrent($sid) : null;
        $t ? Response::success($t) : Response::notFound('no active term');
    }
    public function show(array $p): void { $t=$this->svc->getById((int)($p['id']??0)); $t ? Response::success($t) : Response::notFound('term not found'); }
    public function create(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        try { Response::created($this->svc->create($b),'term created'); } catch(\Throwable $e){ Response::serverError($e); }
    }
    public function update(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        try { $this->svc->update((int)($p['id']??0),$b); Response::success(null,'updated'); } catch(\Throwable $e){ Response::serverError($e); }
    }
    public function setCurrent(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $this->svc->setCurrent((int)($p['id']??0),$sid);
        Response::success(null,'set as current term');
    }
    public function destroy(array $p): void { $this->svc->delete((int)($p['id']??0)); Response::success(null,'deleted'); }
}

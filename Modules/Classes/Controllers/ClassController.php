<?php declare(strict_types=1);
namespace Modules\Classes\Controllers;
use Core\RequestContext;
use Core\Response;
use Modules\Classes\Services\ClassService;

final class ClassController
{
    private ClassService $svc;
    public function __construct(ClassService $svc)
    {
        $this->svc = $svc;
    }

    public function index(array $p): void { $sid=RequestContext::schoolId(); Response::success($sid ? $this->svc->list($sid) : []); }
    public function show(array $p): void { $c=$this->svc->getById((int)($p['id']??0)); $c ? Response::success($c) : Response::notFound('class not found'); }
    public function create(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $b=json_decode(file_get_contents('php://input'),true)??[]; $b['school_id']=$sid;
        try { Response::created($this->svc->create($b),'class created'); } catch(\Throwable $e){ Response::serverError($e); }
    }
    public function update(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        try { $this->svc->update((int)($p['id']??0),$b); Response::success(null,'updated'); } catch(\Throwable $e){ Response::serverError($e); }
    }
    public function destroy(array $p): void { $this->svc->delete((int)($p['id']??0)); Response::success(null,'deleted'); }
    public function subjects(array $p): void { Response::success($this->svc->getSubjects((int)($p['id']??0))); }
    public function assignSubject(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        $this->svc->assignSubject((int)($p['id']??0),(int)($b['subject_id']??0),(bool)($b['is_compulsory']??true));
        Response::success(null,'subject assigned');
    }
    public function removeSubject(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        $this->svc->removeSubject((int)($p['id']??0),(int)($b['subject_id']??0));
        Response::success(null,'removed');
    }
}

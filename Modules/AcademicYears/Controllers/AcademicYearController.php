<?php declare(strict_types=1);
namespace Modules\AcademicYears\Controllers;
use Core\RequestContext;use Core\Response;
use Modules\AcademicYears\Repositories\AcademicYearRepository;
use Modules\AcademicYears\Services\AcademicYearService;

final class AcademicYearController
{
    private AcademicYearService $svc;
    public function __construct() { $this->svc=new AcademicYearService(new AcademicYearRepository()); }

    public function index(array $p): void { $sid=RequestContext::schoolId(); Response::success($sid ? $this->svc->list($sid) : []); }
    public function show(array $p): void { $ay=$this->svc->getById((int)($p['id']??0)); $ay ? Response::success($ay) : Response::notFound('not found'); }
    public function create(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $b=json_decode(file_get_contents('php://input'),true)??[]; $b['school_id']=$sid;
        try { Response::created($this->svc->create($b),'academic year created'); } catch(\Throwable $e){ Response::serverError($e); }
    }
    public function update(array $p): void {
        $b=json_decode(file_get_contents('php://input'),true)??[];
        try { $this->svc->update((int)($p['id']??0),$b); Response::success(null,'updated'); } catch(\Throwable $e){ Response::serverError($e); }
    }
    public function setCurrent(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $this->svc->setCurrent((int)($p['id']??0),$sid); Response::success(null,'set as current year');
    }
    public function destroy(array $p): void { $this->svc->delete((int)($p['id']??0)); Response::success(null,'deleted'); }
}

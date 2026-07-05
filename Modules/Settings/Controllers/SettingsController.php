<?php declare(strict_types=1);
namespace Modules\Settings\Controllers;
use Core\RequestContext;
use Core\Response;
use Modules\Settings\Repositories\SettingsRepository;
use Modules\Settings\Services\SettingsService;

final class SettingsController
{
    private SettingsService $svc;
    public function __construct() { $this->svc=new SettingsService(new SettingsRepository()); }

    public function getSchool(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $s=$this->svc->getSchool($sid); $s ? Response::success($s) : Response::notFound('school not found');
    }

    public function updateSchool(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $b=json_decode(file_get_contents('php://input'),true)??[];
        try { $this->svc->updateSchool($sid,$b); Response::success(null,'school profile updated'); }
        catch(\Throwable $e){ Response::serverError($e); }
    }

    public function updateLogo(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $b=json_decode(file_get_contents('php://input'),true)??[];
        $url=trim($b['logo_url']??''); if($url===''){Response::badRequest('logo_url is required');return;}
        try { $this->svc->updateLogo($sid,$url); Response::success(null,'school logo updated'); }
        catch(\Throwable $e){ Response::serverError($e); }
    }

    public function getSchoolSettings(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $s=$this->svc->getSchoolSettings($sid); $s ? Response::success($s) : Response::notFound('school settings not found');
    }

    public function updateSchoolSettings(array $p): void {
        $sid=RequestContext::schoolId(); if(!$sid){Response::forbidden('no school context');return;}
        $b=json_decode(file_get_contents('php://input'),true)??[];
        try { $this->svc->updateSchoolSettings($sid,$b); Response::success(null,'school settings updated'); }
        catch(\Throwable $e){ Response::serverError($e); }
    }
}

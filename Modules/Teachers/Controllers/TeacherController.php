<?php
declare(strict_types=1);

namespace Modules\Teachers\Controllers;

use Core\RequestContext;
use Core\Response;
use Modules\Teachers\Services\TeacherService;

final class TeacherController
{
    public function __construct(private TeacherService $svc) {}

    public function index(array $p): void
    {
        $sid = RequestContext::schoolId();
        Response::success($sid ? $this->svc->list($sid) : []);
    }

    public function show(array $p): void
    {
        $t = $this->svc->getById((int) ($p['id'] ?? 0));
        $t ? Response::success($t) : Response::notFound('teacher not found');
    }

    public function create(array $p): void
    {
        $sid = RequestContext::schoolId();
        if (!$sid) {
            Response::forbidden('no school context');
            return;
        }

        $b = json_decode(file_get_contents('php://input'), true) ?? [];
        $b['school_id'] = $sid;

        try {
            Response::created($this->svc->create($b), 'teacher enrolled');
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function update(array $p): void
    {
        $b = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $this->svc->update((int) ($p['id'] ?? 0), $b);
            Response::success(null, 'updated');
        } catch (\InvalidArgumentException | \RuntimeException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function destroy(array $p): void
    {
        $this->svc->deactivate((int) ($p['id'] ?? 0));
        Response::success(null, 'deactivated');
    }

    public function subjects(array $p): void
    {
        Response::success($this->svc->getSubjects((int) ($p['id'] ?? 0)));
    }

    public function assignSubject(array $p): void
    {
        $b = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $this->svc->assignSubject(
                (int) ($p['id'] ?? 0),
                (int) ($b['subject_id'] ?? 0),
                (int) ($b['class_id'] ?? 0)
            );
            Response::success(null, 'subject assigned');
        } catch (\InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function removeSubject(array $p): void
    {
        $b = json_decode(file_get_contents('php://input'), true) ?? [];
        $this->svc->removeSubject(
            (int) ($p['id'] ?? 0),
            (int) ($b['subject_id'] ?? 0),
            (int) ($b['class_id'] ?? 0)
        );
        Response::success(null, 'subject removed');
    }
}
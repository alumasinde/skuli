<?php
declare(strict_types=1);

namespace Modules\Subjects\Controllers;

use Core\RequestContext;
use Core\Response;
use Modules\Subjects\Services\SubjectService;

final class SubjectController
{
    public function __construct(private SubjectService $svc) {}

    public function index(array $p): void
    {
        $sid = RequestContext::schoolId();
        Response::success($sid ? $this->svc->list($sid) : []);
    }

    public function show(array $p): void
    {
        $s = $this->svc->getById((int) ($p['id'] ?? 0));
        $s ? Response::success($s) : Response::notFound('subject not found');
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
            Response::created($this->svc->create($b), 'subject created');
        } catch (\InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (\RuntimeException $e) {
            // e.g. duplicate code — a client error, not a 500.
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
        } catch (\InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (\RuntimeException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function destroy(array $p): void
    {
        $this->svc->delete((int) ($p['id'] ?? 0));
        Response::success(null, 'deleted');
    }
}
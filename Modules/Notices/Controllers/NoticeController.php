<?php
declare(strict_types=1);

namespace Modules\Notices\Controllers;

use Core\RequestContext;
use Core\Response;
use Modules\Notices\Repositories\NoticeRepository;
use Modules\Notices\Services\NoticeService;

final class NoticeController
{
    private NoticeService $service;

    public function __construct()
    {
        $this->service = new NoticeService(new NoticeRepository());
    }

    public function create(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $body['school_id'] = $schoolId;

        if (empty($body['title']) || empty($body['body'])) {
            Response::badRequest('title and body are required.'); return;
        }
        try {
            $n = $this->service->create($body, RequestContext::userId());
            Response::created($n, 'notice published');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function index(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::success([]); return; }

        // Caller can filter by audience; if not supplied, derive from role
        $audience = $_GET['audience'] ?? $this->audienceFromRole();
        Response::success($this->service->list($schoolId, $audience));
    }

    public function show(array $params): void
    {
        $n = $this->service->getById((int)($params['id'] ?? 0));
        $n ? Response::success($n) : Response::notFound('notice not found');
    }

    public function destroy(array $params): void
    {
        try {
            $this->service->delete((int)($params['id'] ?? 0));
            Response::success(null, 'notice deleted');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    private function audienceFromRole(): string
    {
        $roles = RequestContext::roles();
        if (in_array('parent', $roles, true))  return 'parents';
        if (in_array('student', $roles, true)) return 'students';
        if (in_array('teacher', $roles, true)) return 'teachers';
        return ''; // admin sees all
    }
}

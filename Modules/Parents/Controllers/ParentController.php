<?php
declare(strict_types=1);

namespace Modules\Parents\Controllers;

use Core\RequestContext;
use Core\Response;
use Modules\Parents\Repositories\ParentRepository;
use Modules\Parents\Services\ParentService;

final class ParentController
{
    private ParentService $service;

    public function __construct()
    {
        $this->service = new ParentService(new ParentRepository());
    }

    public function index(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            Response::success([]);
            return;
        }
        Response::success($this->service->list($schoolId));
    }

    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $parent = $this->service->getById($id);
        if (!$parent) {
            Response::notFound('parent not found');
            return;
        }
        Response::success($parent);
    }

    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $this->service->update($id, $body);
            Response::success(null, 'parent updated');
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }

    public function linkStudent(array $params): void
    {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $parentId     = (int)($body['parent_id'] ?? 0);
        $studentId    = (int)($body['student_id'] ?? 0);
        $relationship = $body['relationship'] ?? 'parent';

        if ($parentId === 0 || $studentId === 0) {
            Response::badRequest('parent_id and student_id are required.');
            return;
        }

        $this->service->linkStudent($parentId, $studentId, $relationship, RequestContext::userId());
        Response::success(null, 'parent linked to student');
    }

    /**
     * GET /parents/me — resolves the parents.id for the CURRENT user from
     * the JWT's user_id. No client-supplied ID is ever trusted here.
     */
    public function me(array $params): void
    {
        $userId = RequestContext::userId();
        $parent = $this->service->getOwnRecord($userId);

        if (!$parent) {
            Response::notFound('no parent record linked to this account');
            return;
        }
        Response::success($parent);
    }

    /**
     * PUT /parents/me — updates the CURRENT user's own parent record only.
     * The parent ID is resolved server-side, never accepted from the request,
     * so there's no way to pass a different parent's ID and edit their data.
     */
    public function updateMe(array $params): void
    {
        $userId = RequestContext::userId();
        $parent = $this->service->getOwnRecord($userId);

        if (!$parent) {
            Response::notFound('no parent record linked to this account');
            return;
        }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        try {
            $this->service->update((int)$parent['id'], $body);
            Response::success(null, 'profile updated');
        } catch (\Throwable $e) {
            Response::serverError($e);
        }
    }
}

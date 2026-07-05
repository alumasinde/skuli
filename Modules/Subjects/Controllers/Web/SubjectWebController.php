<?php
declare(strict_types=1);

namespace Modules\Subjects\Controllers\Web;

use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Subjects\Services\SubjectService;

final class SubjectWebController extends WebController
{
    private SubjectService $service;

    public function __construct(SubjectService $service)
    {
        parent::__construct(); // hydrates RequestContext from Session
        $this->service = $service;
    }

    /** GET /subjects — list subjects for the current school. */
    public function index(array $params): void
    {
        $schoolId = RequestContext::schoolId();

        $this->view('subjects/index', [
            'title'    => 'Subjects',
            'subjects' => $schoolId === null ? [] : $this->service->list($schoolId),
        ]);
    }

    /** GET /subjects/create — show the "new subject" form. */
    public function create(array $params): void
    {
        $this->view('subjects/create', [
            'title'  => 'Add Subject',
            'errors' => Session::flash('errors') ?: [],
            'old'    => Session::flash('old') ?: [],
        ]);
    }

    /** POST /subjects — handle the "new subject" form submission. */
    public function store(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/subjects', 'No school context — cannot create subject.', 'error');
            return;
        }

        $body = [
            'school_id' => $schoolId,
            'name'      => trim($_POST['name'] ?? ''),
            'code'      => strtoupper(trim($_POST['code'] ?? '')),
        ];

        // Cheap presence checks here; the service enforces the real rules
        // (required fields + code uniqueness) and throws on violation.
        if ($body['name'] === '' || $body['code'] === '') {
            Session::flash('errors', ['Subject name and code are required.']);
            Session::flash('old', $body);
            $this->redirect('/subjects/create');
            return;
        }

        try {
            $this->service->create($body);
            $this->redirect('/subjects', 'Subject created successfully.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/subjects/create');
        }
    }

    /** GET /subjects/{id} — subject profile page. */
    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/subjects', 'Invalid subject.', 'error');
            return;
        }

        $subject = $this->service->getById($id);
        if (!$subject) {
            $this->redirect('/subjects', 'Subject not found.', 'error');
            return;
        }

        $this->view('subjects/show', [
            'title'   => $subject['name'] ?? 'Subject',
            'subject' => $subject,
            'classes' => $this->service->classesUsing($id),
        ]);
    }

    /** GET /subjects/{id}/edit — show the edit form. */
    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/subjects', 'Invalid subject.', 'error');
            return;
        }

        $subject = $this->service->getById($id);
        if (!$subject) {
            $this->redirect('/subjects', 'Subject not found.', 'error');
            return;
        }

        $this->view('subjects/edit', [
            'title'   => 'Edit Subject',
            'subject' => $subject,
            'errors'  => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /subjects/{id}/update — handle the edit form submission. */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/subjects', 'Invalid subject.', 'error');
            return;
        }

        $existing = $this->service->getById($id);
        if (!$existing) {
            $this->redirect('/subjects', 'Subject not found.', 'error');
            return;
        }

        $body = [
            'name' => trim($_POST['name'] ?? ''),
            'code' => strtoupper(trim($_POST['code'] ?? '')),
        ];

        if ($body['name'] === '' || $body['code'] === '') {
            Session::flash('errors', ['Subject name and code are required.']);
            $this->redirect("/subjects/{$id}/edit");
            return;
        }

        try {
            $this->service->update($id, $body);
            $this->redirect("/subjects/{$id}", 'Subject updated.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/subjects/{$id}/edit");
        }
    }

    /** POST /subjects/{id}/delete — soft-delete a subject. */
    public function destroy(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/subjects', 'Invalid subject.', 'error');
            return;
        }

        $this->service->delete($id);
        $this->redirect('/subjects', 'Subject deleted.');
    }
}
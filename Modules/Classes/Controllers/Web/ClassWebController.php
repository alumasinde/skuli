<?php
declare(strict_types=1);

namespace Modules\Classes\Controllers\Web;

use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Classes\Services\ClassService;

final class ClassWebController extends WebController
{
    private ClassService $service;

    public function __construct(ClassService $service)
    {
        parent::__construct(); 
        $this->service = $service;
    }

    /** GET /classes — list classes for the current school. */
    public function index(array $params): void
    {
        $schoolId = RequestContext::schoolId();

        if ($schoolId === null) {
            $this->view('classes/index', [
                'title' => 'Classes', 'classes' => [],
            ]);
            return;
        }

        $this->view('classes/index', [
            'title'   => 'Classes',
            'classes' => $this->service->list($schoolId),
        ]);
    }

    /** GET /classes/create — show the "new class" form. */
    public function create(array $params): void
    {
        $this->view('classes/create', [
            'title'  => 'Add Class',
            'errors' => Session::flash('errors') ?: [],
            'old'    => Session::flash('old') ?: [],
        ]);
    }

    /** POST /classes — handle the "new class" form submission. */
    public function store(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/classes', 'No school context — cannot create class.', 'error');
            return;
        }

        $body = $_POST;
        $body['school_id'] = $schoolId;

        if (empty($body['name'])) {
            Session::flash('errors', ['Class name is required.']);
            Session::flash('old', $body);
            $this->redirect('/classes/create');
            return;
        }

        try {
            $this->service->create($body);
            $this->redirect('/classes', 'Class created successfully.');
        } catch (\Throwable $e) {
            Session::flash('errors', ['Could not create class: ' . $e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/classes/create');
        }
    }

    /** GET /classes/{id} — class profile page, including its subjects. */
    public function show(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/classes', 'Invalid class.', 'error');
            return;
        }

        $class = $this->service->getById($id);
        if (!$class) {
            $this->redirect('/classes', 'Class not found.', 'error');
            return;
        }

        $this->view('classes/show', [
            'title'    => $class['name'] ?? 'Class',
            'class'    => $class,
            'subjects' => $this->service->getSubjects($id),
        ]);
    }

    /** GET /classes/{id}/edit — show the edit form. */
    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/classes', 'Invalid class.', 'error');
            return;
        }

        $class = $this->service->getById($id);
        if (!$class) {
            $this->redirect('/classes', 'Class not found.', 'error');
            return;
        }

        $this->view('classes/edit', [
            'title'  => 'Edit Class',
            'class'  => $class,
            'errors' => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /classes/{id}/update — handle the edit form submission. */
    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/classes', 'Invalid class.', 'error');
            return;
        }

        $existing = $this->service->getById($id);
        if (!$existing) {
            $this->redirect('/classes', 'Class not found.', 'error');
            return;
        }

        $body = $_POST;
        if (empty($body['name'])) {
            Session::flash('errors', ['Class name is required.']);
            $this->redirect("/classes/{$id}/edit");
            return;
        }

        try {
            $this->service->update($id, $body);
            $this->redirect("/classes/{$id}", 'Class updated.');
        } catch (\Throwable $e) {
            Session::flash('errors', ['Could not update class: ' . $e->getMessage()]);
            $this->redirect("/classes/{$id}/edit");
        }
    }

    /** POST /classes/{id}/delete — remove a class. */
    public function destroy(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/classes', 'Invalid class.', 'error');
            return;
        }

        $this->service->delete($id);
        $this->redirect('/classes', 'Class deleted.');
    }

    /** POST /classes/{id}/subjects — assign a subject to a class. */
    public function assignSubject(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/classes', 'Invalid class.', 'error');
            return;
        }

        $subjectId    = (int)($_POST['subject_id'] ?? 0);
        $isCompulsory = isset($_POST['is_compulsory']);

        if ($subjectId === 0) {
            Session::flash('errors', ['Please select a subject.']);
            $this->redirect("/classes/{$id}");
            return;
        }

        try {
            $this->service->assignSubject($id, $subjectId, $isCompulsory);
            $this->redirect("/classes/{$id}", 'Subject assigned.');
        } catch (\Throwable $e) {
            Session::flash('errors', ['Could not assign subject: ' . $e->getMessage()]);
            $this->redirect("/classes/{$id}");
        }
    }

    /** POST /classes/{id}/subjects/remove — remove a subject from a class. */
    public function removeSubject(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/classes', 'Invalid class.', 'error');
            return;
        }

        $subjectId = (int)($_POST['subject_id'] ?? 0);
        $this->service->removeSubject($id, $subjectId);
        $this->redirect("/classes/{$id}", 'Subject removed.');
    }
}
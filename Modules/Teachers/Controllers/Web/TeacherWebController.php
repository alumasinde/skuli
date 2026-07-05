<?php
declare(strict_types=1);

namespace Modules\Teachers\Controllers\Web;

use Core\ImageUploadService;
use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Classes\Services\ClassService;
use Modules\Subjects\Services\SubjectService;
use Modules\Teachers\Services\TeacherService;

final class TeacherWebController extends WebController
{
    public function __construct(
        private TeacherService $service,
        private ClassService   $classes,
        private SubjectService $subjects,
        private ImageUploadService $images
    ) {
        parent::__construct(); // hydrates RequestContext from Session
    }

    /** GET /teachers — list teachers for the current school. */
    public function index(array $params): void
    {
        $schoolId = RequestContext::schoolId();

        $this->view('teachers/index', [
            'title'    => 'Teachers',
            'teachers' => $schoolId === null ? [] : $this->service->list($schoolId),
        ]);
    }

    /** GET /teachers/create — enrollment form. */
    public function create(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/teachers', 'No school context — cannot add teacher.', 'error');
            return;
        }

        $this->view('teachers/create', [
            'title'  => 'Add Teacher',
            'users'  => $this->service->linkableUsers($schoolId),
            'errors' => Session::flash('errors') ?: [],
            'old'    => Session::flash('old') ?: [],
        ]);
    }

    /** POST /teachers — handle enrollment. */
    public function store(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/teachers', 'No school context — cannot add teacher.', 'error');
            return;
        }

        $body = $_POST;
        $body['school_id']        = $schoolId;
        $body['is_class_teacher'] = isset($_POST['is_class_teacher']) ? 1 : 0;

        if (empty($body['user_id']) || empty($body['employee_no'])) {
            Session::flash('errors', ['Please select a user account and enter an employee number.']);
            Session::flash('old', $body);
            $this->redirect('/teachers/create');
            return;
        }

        try {
            $body['photo_url'] = $this->images->handle($_FILES['photo'] ?? null, 'teachers') ?: null;
        } catch (\Throwable $e) {
            Session::flash('errors', ['Photo upload failed: ' . $e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/teachers/create');
            return;
        }

        try {
            $teacher = $this->service->create($body);
            $this->redirect('/teachers/' . $teacher['id'], 'Teacher added successfully.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/teachers/create');
        }
    }

    /** GET /teachers/{id} — profile + subject assignments. */
    public function show(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/teachers', 'Invalid teacher.', 'error');
            return;
        }

        $teacher = $this->service->getById($id);
        if (!$teacher) {
            $this->redirect('/teachers', 'Teacher not found.', 'error');
            return;
        }

        $schoolId = RequestContext::schoolId();

        $this->view('teachers/show', [
            'title'    => trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? '')),
            'teacher'  => $teacher,
            'subjects' => $this->service->getSubjects($id),
            // For the "assign subject" form:
            'allClasses'  => $schoolId ? $this->classes->list($schoolId) : [],
            'allSubjects' => $schoolId ? $this->subjects->list($schoolId) : [],
            'errors'      => Session::flash('errors') ?: [],
        ]);
    }

    /** GET /teachers/{id}/edit — edit form. */
    public function edit(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/teachers', 'Invalid teacher.', 'error');
            return;
        }

        $teacher = $this->service->getById($id);
        if (!$teacher) {
            $this->redirect('/teachers', 'Teacher not found.', 'error');
            return;
        }

        $this->view('teachers/edit', [
            'title'   => 'Edit Teacher',
            'teacher' => $teacher,
            'errors'  => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /teachers/{id}/update — handle edit. */
    public function update(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/teachers', 'Invalid teacher.', 'error');
            return;
        }

        $existing = $this->service->getById($id);
        if (!$existing) {
            $this->redirect('/teachers', 'Teacher not found.', 'error');
            return;
        }

        $body = $_POST;
        $body['is_class_teacher'] = isset($_POST['is_class_teacher']) ? 1 : 0;

        // Keep the current photo unless a new one is uploaded.
        $body['photo_url'] = $existing['photo_url'] ?? null;
        try {
            $newPhoto = $this->images->handle($_FILES['photo'] ?? null, 'teachers');
            if ($newPhoto !== '') {
                $body['photo_url'] = $newPhoto;
                $this->images->delete($existing['photo_url'] ?? '');
            }
        } catch (\Throwable $e) {
            Session::flash('errors', ['Photo upload failed: ' . $e->getMessage()]);
            $this->redirect("/teachers/{$id}/edit");
            return;
        }

        try {
            $this->service->update($id, $body);
            $this->redirect("/teachers/{$id}", 'Teacher updated.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/teachers/{$id}/edit");
        }
    }

    /** POST /teachers/{id}/delete — soft-delete. */
    public function destroy(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/teachers', 'Invalid teacher.', 'error');
            return;
        }

        $this->service->deactivate($id);
        $this->redirect('/teachers', 'Teacher deactivated.');
    }

    /** POST /teachers/{id}/subjects — assign a subject+class to this teacher. */
    public function assignSubject(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/teachers', 'Invalid teacher.', 'error');
            return;
        }

        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $classId   = (int) ($_POST['class_id'] ?? 0);

        try {
            $this->service->assignSubject($id, $subjectId, $classId);
            $this->redirect("/teachers/{$id}", 'Subject assigned.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/teachers/{$id}");
        }
    }

    /** POST /teachers/{id}/subjects/remove — unassign a subject+class. */
    public function removeSubject(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/teachers', 'Invalid teacher.', 'error');
            return;
        }

        $subjectId = (int) ($_POST['subject_id'] ?? 0);
        $classId   = (int) ($_POST['class_id'] ?? 0);

        $this->service->removeSubject($id, $subjectId, $classId);
        $this->redirect("/teachers/{$id}", 'Subject removed.');
    }
}
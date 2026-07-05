<?php
declare(strict_types=1);

namespace Modules\Students\Controllers\Web;

use Core\ImageUploadService;
use Core\Ownership;
use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Students\Services\StudentService;
use Modules\Settings\Services\SettingsService;

final class StudentWebController extends WebController
{
    private StudentService $service;
    private ImageUploadService $images;
 
    public function __construct(
        StudentService $service,
        private SettingsService $settings
    ) {
        parent::__construct();
        $this->service = $service;
        $this->images  = new ImageUploadService();
    }

    public function index(array $params): void
    {
        $roles    = RequestContext::roles();
        $userId   = RequestContext::userId();
        $schoolId = RequestContext::schoolId();

        if (in_array('parent', $roles, true)) {
            $this->view('students/index', [
                'title' => 'Students', 'students' => $this->service->listByParentUser($userId),
                'pagination' => null, 'search' => '',
            ]);
            return;
        }

        if (in_array('teacher', $roles, true)) {
            $this->view('students/index', [
                'title' => 'Students', 'students' => $this->service->listByTeacherUser($userId),
                'pagination' => null, 'search' => '',
            ]);
            return;
        }

        if ($schoolId === null) {
            $this->view('students/index', [
                'title' => 'Students', 'students' => [], 'pagination' => null, 'search' => '',
            ]);
            return;
        }

        $search = trim($_GET['q'] ?? '');

        if ($search !== '' && mb_strlen($search) >= 2) {
            $this->view('students/index', [
                'title' => 'Students', 'students' => $this->service->search($schoolId, $search),
                'pagination' => null, 'search' => $search,
            ]);
            return;
        }

        $page    = max(1, (int)($_GET['page'] ?? 1));
        $perPage = max(1, (int)($_GET['per_page'] ?? 20));

        $result     = $this->service->listByAdmin($schoolId, $page, $perPage);
        $totalPages = $perPage > 0 ? (int)ceil($result['total'] / $perPage) : 1;

        $this->view('students/index', [
            'title' => 'Students', 'students' => $result['list'],
            'pagination' => [
                'page' => $page, 'per_page' => $perPage,
                'total' => $result['total'], 'total_pages' => $totalPages,
            ],
            'search' => '',
        ]);
    }

    /** GET /students/create — show the enrollment form. */
    public function create(array $params): void
{
    $schoolId = RequestContext::schoolId();

    $this->view('students/create', [
        'title'   => 'Enroll Student',
        'classes' => $this->service->getClasses((int)$schoolId),
        'errors'  => Session::flash('errors') ?: [],
        'old'     => Session::flash('old') ?: [],
    ]);
}
    public function store(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) {
            $this->redirect('/students', 'No school context — cannot create student.', 'error');
            return;
        }

        $body = $_POST;
        $body['school_id'] = $schoolId;

        if (empty($body['first_name']) || empty($body['last_name']) || empty($body['class_id'])) {
            Session::flash('errors', ['First name, last name, and class are required.']);
            Session::flash('old', $body);
            $this->redirect('/students/create');
            return;
        }

        try {
            // Optional photo. Field name in the form must be name="photo".
            $body['photo_url'] = $this->images->handle($_FILES['photo'] ?? null, 'students');
        } catch (\Throwable $e) {
            Session::flash('errors', ['Photo upload failed: ' . $e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/students/create');
            return;
        }

        try {
            $this->service->create($body);
            $this->redirect('/students', 'Student enrolled successfully.');
        } catch (\Throwable $e) {
            Session::flash('errors', ['Could not enroll student: ' . $e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/students/create');
        }
    }
public function show(array $params): void
{
    $id = (int) ($params['id'] ?? 0);
 
    if ($id === 0) {
        $this->redirect('/students', 'Invalid student.', 'error');
        return;
    }
 
    if (!Ownership::canAccessStudent($id)) {
        $this->redirect('/students', 'You do not have access to this student.', 'error');
        return;
    }
 
    try {
        $profile = $this->service->studentProfile($id);
 
        $this->view('students/show', [
            'title'   => $profile['title'],
            'student' => $profile,
        ]);
    } catch (\Throwable $e) {

        $this->redirect('/students', 'Could not load student profile.', 'error');
    }
}
 

 public function idCard(array $params): void
    {
        $id = (int) ($params['id'] ?? 0);
 
        if ($id === 0) {
            $this->redirect('/students', 'Invalid student.', 'error');
            return;
        }
 
        if (!Ownership::canAccessStudent($id)) {
            $this->redirect('/students', 'You do not have access to this student.', 'error');
            return;
        }
 
        $student = $this->service->getById($id);
        if (!$student) {
            $this->redirect('/students', 'Student not found.', 'error');
            return;
        }
 
        $schoolId = RequestContext::schoolId();
        $school   = $schoolId ? $this->settings->getSchool($schoolId) : null;

        extract(['title' => 'ID Card — ' . trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')), 'student' => $student, 'school' => $school]);
        require dirname(__DIR__, 4) . '/views/students/id_card.php';
    }    

    public function edit(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/students', 'Invalid student.', 'error');
            return;
        }

        $student = $this->service->getById($id);
        if (!$student) {
            $this->redirect('/students', 'Student not found.', 'error');
            return;
        }

        $this->view('students/edit', [
            'title'   => 'Edit Student',
            'student' => $student,
            'errors'  => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /students/{id}/update — handle the edit form submission. */
    public function update(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/students', 'Invalid student.', 'error');
            return;
        }

        $existing = $this->service->getById($id);
        if (!$existing) {
            $this->redirect('/students', 'Student not found.', 'error');
            return;
        }

        $body = $_POST;
        if (empty($body['first_name']) || empty($body['last_name'])) {
            Session::flash('errors', ['First name and last name are required.']);
            $this->redirect("/students/{$id}/edit");
            return;
        }

        // Keep the current photo unless a new one is uploaded.
        $body['photo_url'] = $existing['photo_url'] ?? '';
        try {
            $newPhoto = $this->images->handle($_FILES['photo'] ?? null, 'students');
            if ($newPhoto !== '') {
                $body['photo_url'] = $newPhoto;
                // Remove the old file now that the replacement is stored.
                $this->images->delete($existing['photo_url'] ?? '');
            }
        } catch (\Throwable $e) {
            Session::flash('errors', ['Photo upload failed: ' . $e->getMessage()]);
            $this->redirect("/students/{$id}/edit");
            return;
        }

        try {
            $this->service->update($id, $body);
            $this->redirect("/students/{$id}", 'Student updated.');
        } catch (\Throwable $e) {
            Session::flash('errors', ['Could not update student: ' . $e->getMessage()]);
            $this->redirect("/students/{$id}/edit");
        }
    }

    /** POST /students/{id}/delete — deactivate (soft delete) a student. */
    public function destroy(array $params): void
    {
        $id = (int)($params['id'] ?? 0);
        if ($id === 0) {
            $this->redirect('/students', 'Invalid student.', 'error');
            return;
        }

        $this->service->deactivate($id, RequestContext::userId());
        $this->redirect('/students', 'Student deactivated.');
    }
}
<?php
declare(strict_types=1);

use Core\Router;
use Core\Middleware\Authenticate;
use Core\Middleware\RequirePermission;

use Modules\Auth\Controllers\AuthController;
use Modules\Students\Controllers\StudentController;
use Modules\Parents\Controllers\ParentController;
use Modules\Finance\Controllers\FinanceController;
use Modules\Exams\Controllers\ExamController;
use Modules\Attendance\Controllers\AttendanceController;
use Modules\Discipline\Controllers\DisciplineController;
use Modules\Notices\Controllers\NoticeController;
use Modules\Reports\Controllers\ReportController;
use Modules\Teachers\Controllers\TeacherController;
use Modules\Classes\Controllers\ClassController;
use Modules\Subjects\Controllers\SubjectController;
use Modules\Terms\Controllers\TermController;
use Modules\AcademicYears\Controllers\AcademicYearController;
use Modules\Users\Controllers\UserController;
use Modules\Settings\Controllers\SettingsController;

/** @var Router $router */

// ── Public ─────────────────────────────────────────────────────────────────
$router->group('/api/v1/auth', [], function (Router $r) {
    $r->post('/login',   [AuthController::class, 'login']);
    $r->post('/refresh', [AuthController::class, 'refresh']);
    $r->post('/logout',  [AuthController::class, 'logout']);
});

// ── Protected ──────────────────────────────────────────────────────────────
$router->group('/api/v1', [new Authenticate()], function (Router $r) {

    // Students
    $r->group('/students', [], function (Router $r) {
        $r->get('/',                        [StudentController::class,'index'],       [RequirePermission::for('students.view')]);
        $r->post('/',                       [StudentController::class,'create'],      [RequirePermission::for('students.create')]);
        $r->get('/search',                  [StudentController::class,'search'],      [RequirePermission::for('students.view')]);
        $r->get('/class/{classId}',         [StudentController::class,'listByClass'], [RequirePermission::for('students.view')]);
        $r->get('/{id}',                    [StudentController::class,'show'],        [RequirePermission::for('students.view')]);
        $r->put('/{id}',                    [StudentController::class,'update'],      [RequirePermission::for('students.edit')]);
        $r->delete('/{id}',                 [StudentController::class,'destroy'],     [RequirePermission::for('students.delete')]);
        $r->get('/{id}/parents',            [StudentController::class,'parents'],     [RequirePermission::for('students.view')]);
    });

    // Teachers
    $r->group('/teachers', [], function (Router $r) {
        $r->get('/',                        [TeacherController::class,'index'],       [RequirePermission::for('teachers.view')]);
        $r->post('/',                       [TeacherController::class,'create'],      [RequirePermission::for('teachers.create')]);
        $r->get('/{id}',                    [TeacherController::class,'show'],        [RequirePermission::for('teachers.view')]);
        $r->put('/{id}',                    [TeacherController::class,'update'],      [RequirePermission::for('teachers.edit')]);
        $r->delete('/{id}',                 [TeacherController::class,'destroy'],     [RequirePermission::for('teachers.delete')]);
        $r->get('/{id}/subjects',           [TeacherController::class,'subjects'],    [RequirePermission::for('teachers.view')]);
        $r->post('/{id}/subjects',          [TeacherController::class,'assignSubject'],[RequirePermission::for('teachers.edit')]);
        $r->delete('/{id}/subjects',        [TeacherController::class,'removeSubject'],[RequirePermission::for('teachers.edit')]);
    });

    // Classes
    $r->group('/classes', [], function (Router $r) {
        $r->get('/',                        [ClassController::class,'index'],         [RequirePermission::for('classes.view')]);
        $r->post('/',                       [ClassController::class,'create'],        [RequirePermission::for('classes.create')]);
        $r->get('/{id}',                    [ClassController::class,'show'],          [RequirePermission::for('classes.view')]);
        $r->put('/{id}',                    [ClassController::class,'update'],        [RequirePermission::for('classes.edit')]);
        $r->delete('/{id}',                 [ClassController::class,'destroy'],       [RequirePermission::for('classes.delete')]);
        $r->get('/{id}/subjects',           [ClassController::class,'subjects'],      [RequirePermission::for('classes.view')]);
        $r->post('/{id}/subjects',          [ClassController::class,'assignSubject'], [RequirePermission::for('classes.edit')]);
        $r->delete('/{id}/subjects',        [ClassController::class,'removeSubject'], [RequirePermission::for('classes.edit')]);
    });

    // Subjects
    $r->group('/subjects', [], function (Router $r) {
        $r->get('/',                        [SubjectController::class,'index'],       [RequirePermission::for('subjects.view')]);
        $r->post('/',                       [SubjectController::class,'create'],      [RequirePermission::for('subjects.create')]);
        $r->get('/{id}',                    [SubjectController::class,'show'],        [RequirePermission::for('subjects.view')]);
        $r->put('/{id}',                    [SubjectController::class,'update'],      [RequirePermission::for('subjects.edit')]);
        $r->delete('/{id}',                 [SubjectController::class,'destroy'],     [RequirePermission::for('subjects.delete')]);
    });

    // Academic Years
    $r->group('/academic-years', [], function (Router $r) {
        $r->get('/',                        [AcademicYearController::class,'index'],     [RequirePermission::for('academic_years.view')]);
        $r->post('/',                       [AcademicYearController::class,'create'],    [RequirePermission::for('academic_years.create')]);
        $r->get('/{id}',                    [AcademicYearController::class,'show'],      [RequirePermission::for('academic_years.view')]);
        $r->put('/{id}',                    [AcademicYearController::class,'update'],    [RequirePermission::for('academic_years.edit')]);
        $r->put('/{id}/set-current',        [AcademicYearController::class,'setCurrent'],[RequirePermission::for('academic_years.edit')]);
        $r->delete('/{id}',                 [AcademicYearController::class,'destroy'],   [RequirePermission::for('academic_years.edit')]);
    });

    // Terms
    $r->group('/terms', [], function (Router $r) {
        $r->get('/',                        [TermController::class,'index'],          [RequirePermission::for('terms.view')]);
        $r->post('/',                       [TermController::class,'create'],         [RequirePermission::for('terms.create')]);
        $r->get('/current',                 [TermController::class,'current'],        [RequirePermission::for('terms.view')]);
        $r->get('/{id}',                    [TermController::class,'show'],           [RequirePermission::for('terms.view')]);
        $r->put('/{id}',                    [TermController::class,'update'],         [RequirePermission::for('terms.edit')]);
        $r->put('/{id}/set-current',        [TermController::class,'setCurrent'],     [RequirePermission::for('terms.edit')]);
        $r->delete('/{id}',                 [TermController::class,'destroy'],        [RequirePermission::for('terms.delete')]);
    });

    // Parents
    $r->group('/parents', [], function (Router $r) {
        $r->get('/',                        [ParentController::class,'index'],        [RequirePermission::for('parents.view')]);
        $r->get('/me',                      [ParentController::class,'me'],           [RequirePermission::for('parents.view')]);
        $r->put('/me',                      [ParentController::class,'updateMe'],     [RequirePermission::for('parents.view')]);
        $r->post('/link-student',           [ParentController::class,'linkStudent'],  [RequirePermission::for('parents.create')]);
        $r->get('/{id}',                    [ParentController::class,'show'],         [RequirePermission::for('parents.view')]);
        $r->put('/{id}',                    [ParentController::class,'update'],       [RequirePermission::for('parents.create')]);
    });

    // Finance
    $r->group('/finance', [], function (Router $r) {
        $r->get('/fee-types',               [FinanceController::class,'listFeeTypes'],    [RequirePermission::for('finance.view')]);
        $r->post('/fee-types',              [FinanceController::class,'createFeeType'],   [RequirePermission::for('finance.create')]);
        $r->post('/invoices/generate',      [FinanceController::class,'generateInvoices'],[RequirePermission::for('finance.create')]);
        $r->post('/payments',               [FinanceController::class,'recordPayment'],   [RequirePermission::for('finance.create')]);
        $r->get('/invoices/{invoiceId}/payments',[FinanceController::class,'invoicePayments'],[RequirePermission::for('finance.view')]);
        $r->post('/discounts',              [FinanceController::class,'createDiscount'],  [RequirePermission::for('finance.create')]);
        $r->get('/discounts/{studentId}',   [FinanceController::class,'listDiscounts'],   [RequirePermission::for('finance.view')]);
        $r->post('/mpesa/push',             [FinanceController::class,'mpesaStkPush'],    [RequirePermission::for('finance.create')]);
        $r->get('/statement/student/{studentId}',[FinanceController::class,'studentStatement'],[RequirePermission::for('finance.view')]);
    });

    // Exams
    $r->group('/exams', [], function (Router $r) {
        $r->get('/',                        [ExamController::class,'index'],           [RequirePermission::for('exams.view')]);
        $r->post('/',                       [ExamController::class,'create'],          [RequirePermission::for('exams.create')]);
        $r->post('/results',                [ExamController::class,'submitResults'],   [RequirePermission::for('exams.results')]);
        $r->get('/grade-scales',            [ExamController::class,'getGradeScales'],  [RequirePermission::for('exams.view')]);
        $r->post('/grade-scales',           [ExamController::class,'createGradeScale'],[RequirePermission::for('settings.manage')]);
        $r->put('/grade-scales/{id}',       [ExamController::class,'updateGradeScale'],[RequirePermission::for('settings.manage')]);
        $r->delete('/grade-scales/{id}',    [ExamController::class,'deleteGradeScale'],[RequirePermission::for('settings.manage')]);
        $r->get('/student/{studentId}/results',[ExamController::class,'getStudentResults'],[RequirePermission::for('exams.view')]);
        $r->get('/{id}',                    [ExamController::class,'show'],            [RequirePermission::for('exams.view')]);
        $r->get('/{id}/results',            [ExamController::class,'getResults'],      [RequirePermission::for('exams.view')]);
    });

    // Attendance
    $r->group('/attendance', [], function (Router $r) {
        $r->post('/',                       [AttendanceController::class,'mark'],          [RequirePermission::for('attendance.mark')]);
        $r->get('/class/{classId}',         [AttendanceController::class,'getByClassDate'],[RequirePermission::for('attendance.view')]);
        $r->get('/class/{classId}/summary', [AttendanceController::class,'summary'],       [RequirePermission::for('attendance.view')]);
        $r->get('/student/{studentId}',     [AttendanceController::class,'getByStudent'],  [RequirePermission::for('attendance.view')]);
    });

    // Discipline
    $r->group('/discipline', [], function (Router $r) {
        $r->get('/',                        [DisciplineController::class,'index'],        [RequirePermission::for('discipline.view')]);
        $r->post('/',                       [DisciplineController::class,'create'],       [RequirePermission::for('discipline.create')]);
        $r->get('/student/{studentId}',     [DisciplineController::class,'listByStudent'],[RequirePermission::for('discipline.view')]);
        $r->delete('/{id}',                 [DisciplineController::class,'destroy'],      [RequirePermission::for('discipline.create')]);
    });

    // Notices
    $r->group('/notices', [], function (Router $r) {
        $r->get('/',                        [NoticeController::class,'index'],  [RequirePermission::for('notices.view')]);
        $r->post('/',                       [NoticeController::class,'create'], [RequirePermission::for('notices.create')]);
        $r->get('/{id}',                    [NoticeController::class,'show'],   [RequirePermission::for('notices.view')]);
        $r->delete('/{id}',                 [NoticeController::class,'destroy'],[RequirePermission::for('notices.create')]);
    });

    // Reports
    $r->group('/reports', [], function (Router $r) {
        $r->get('/report-card/{studentId}',         [ReportController::class,'reportCard'],        [RequirePermission::for('reports.view')]);
        $r->put('/report-card/{studentId}/remarks', [ReportController::class,'updateRemarks'],     [RequirePermission::for('reports.view')]);
        $r->get('/class-results',                   [ReportController::class,'classResults'],      [RequirePermission::for('reports.view')]);
        $r->get('/fee-collection',                  [ReportController::class,'feeCollection'],     [RequirePermission::for('reports.view')]);
        $r->get('/attendance-summary',              [ReportController::class,'attendanceSummary'], [RequirePermission::for('reports.view')]);
        $r->get('/subject-performance',             [ReportController::class,'subjectPerformance'],[RequirePermission::for('reports.view')]);
    });

    // Users (admin)
    $r->group('/users', [], function (Router $r) {
        $r->get('/',                        [UserController::class,'index'],        [RequirePermission::for('users.view')]);
        $r->post('/',                       [UserController::class,'create'],       [RequirePermission::for('users.create')]);
        $r->get('/roles',                   [UserController::class,'roles'],        [RequirePermission::for('users.view')]);
        $r->get('/{id}',                    [UserController::class,'show'],         [RequirePermission::for('users.view')]);
        $r->put('/{id}',                    [UserController::class,'update'],       [RequirePermission::for('users.edit')]);
        $r->put('/{id}/activate',           [UserController::class,'activate'],     [RequirePermission::for('users.edit')]);
        $r->put('/{id}/deactivate',         [UserController::class,'deactivate'],   [RequirePermission::for('users.edit')]);
        $r->put('/{id}/reset-password',     [UserController::class,'resetPassword'],[RequirePermission::for('users.edit')]);
        $r->post('/{id}/roles',             [UserController::class,'assignRole'],   [RequirePermission::for('users.edit')]);
        $r->delete('/{id}/roles',           [UserController::class,'removeRole'],   [RequirePermission::for('users.edit')]);
    });

    // Settings
    $r->group('/settings', [], function (Router $r) {
        $r->get('/school',                  [SettingsController::class,'getSchool'],         [RequirePermission::for('settings.manage')]);
        $r->put('/school',                  [SettingsController::class,'updateSchool'],      [RequirePermission::for('settings.manage')]);
        $r->get('/roles',                   [SettingsController::class,'getRoles'],          [RequirePermission::for('settings.manage')]);
        $r->get('/permissions',             [SettingsController::class,'getPermissions'],    [RequirePermission::for('settings.manage')]);
        $r->get('/roles/{roleId}/permissions',    [SettingsController::class,'getRolePermissions'], [RequirePermission::for('settings.manage')]);
        $r->post('/roles/{roleId}/permissions',   [SettingsController::class,'grantPermission'],    [RequirePermission::for('settings.manage')]);
        $r->delete('/roles/{roleId}/permissions', [SettingsController::class,'revokePermission'],   [RequirePermission::for('settings.manage')]);
    });

    // Schools lookup (used by web controllers)
    $r->get('/schools/{id}', function(array $p) {
        $s = (new \Modules\Settings\Repositories\SettingsRepository())->getSchool((int)($p['id']??0));
        $s ? \Core\Response::success($s) : \Core\Response::notFound('school not found');
    }, [RequirePermission::for('students.view')]);
});

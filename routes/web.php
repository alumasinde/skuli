<?php
declare(strict_types=1);

use Core\Router;
use Core\Middleware\AuthenticateWeb;
use Core\Middleware\VerifyCsrf;
use Core\Middleware\RateLimitMiddleware;
use Modules\Auth\Controllers\WebAuthController;
use Modules\Dashboard\Controllers\DashboardController;
use Modules\Students\Controllers\Web\StudentWebController;
use Modules\Teachers\Controllers\Web\TeacherWebController;
use Modules\Classes\Controllers\Web\ClassWebController;
use Modules\Subjects\Controllers\Web\SubjectWebController;
use Modules\Terms\Controllers\TermController;
use Modules\AcademicYears\Controllers\AcademicYearController;
use Modules\Parents\Controllers\Web\ParentsWebController;
use Modules\Finance\Controllers\Web\FinanceWebController;
use Modules\Exams\Controllers\Web\ExamWebController;
use Modules\Attendance\Controllers\AttendanceController;
use Modules\Discipline\Controllers\DisciplineController;
use Modules\Notices\Controllers\NoticeController;
use Modules\Reports\Controllers\Web\ReportsWebController;
use Modules\Users\Controllers\UserController;
use Modules\Settings\Controllers\Web\SettingsWebController;
use Modules\SuperAdmin\Controllers\Web\SuperAdminController;
use Modules\Billing\Controllers\Web\BillingWebController;
use Modules\SuperAdmin\Controllers\Web\SuperAdminBillingController;
use Modules\Marketing\Controllers\Web\MarketingController;
use Modules\SuperAdmin\Controllers\Web\SuperAdminDemoRequestsController;
use Modules\AuditLog\Controllers\Web\AuditLogWebController;


/** @var Router $router */

$auth = [new AuthenticateWeb()];
$csrf = [new VerifyCsrf()];
$both = [new AuthenticateWeb(), new VerifyCsrf()];
$guest = [];
$csrfOnly = [new VerifyCsrf()];
$rateLimitedAuth = [new RateLimitMiddleware('auth'), new VerifyCsrf()];


// ── Auth (public) ──────────────────────────────────────────────────────────
$router->get('/login',  [WebAuthController::class, 'showLogin']);
$router->post('/login', [WebAuthController::class, 'login'],  $rateLimitedAuth);
$router->post('/login/find-school', [WebAuthController::class, 'findSchool'], $rateLimitedAuth);
$router->get('/logout', [WebAuthController::class, 'logout'], $auth);

// ── Dashboard ──────────────────────────────────────────────────────────────
$router->get('/dashboard', DashboardController::class . '@index', $auth);

// ── Students ────────────────────────────────────────────────────────────────
$router->get('/students', StudentWebController::class . '@index', $auth);
$router->get('/students/create',       [StudentWebController::class, 'create'],  $auth);
$router->post('/students',             [StudentWebController::class, 'store'],   $both);
$router->get('/students/{id}',         [StudentWebController::class, 'show'],    $auth);
$router->get('/students/{id}/edit',    [StudentWebController::class, 'edit'],    $auth);
$router->post('/students/{id}/update', [StudentWebController::class, 'update'],  $both);
$router->post('/students/{id}/delete', [StudentWebController::class, 'destroy'], $both);
$router->get('/students/{id}/id-card', [StudentWebController::class, 'idCard'], $auth);


// ── Teachers ─────────────────────────────────────────────────────────────────
$router->get('/teachers',              [TeacherWebController::class, 'index'],   $auth);
$router->get('/teachers/create',       [TeacherWebController::class, 'create'],  $auth);
$router->post('/teachers',             [TeacherWebController::class, 'store'],   $both);
$router->get('/teachers/{id}',         [TeacherWebController::class, 'show'],    $auth);
$router->get('/teachers/{id}/edit',    [TeacherWebController::class, 'edit'],    $auth);
$router->post('/teachers/{id}/update', [TeacherWebController::class, 'update'],  $both);
$router->post('/teachers/{id}/delete', [TeacherWebController::class, 'destroy'], $both);

// ── Classes ───────────────────────────────────────────────────────────────────
$router->get('/classes',               [ClassWebController::class, 'index'],   $auth);
$router->get('/classes/create',        [ClassWebController::class, 'create'],  $auth);
$router->post('/classes',              [ClassWebController::class, 'store'],   $both);
$router->get('/classes/{id}',          [ClassWebController::class, 'show'],    $auth);
$router->get('/classes/{id}/edit',     [ClassWebController::class, 'edit'],    $auth);
$router->post('/classes/{id}/update',  [ClassWebController::class, 'update'],  $both);
$router->post('/classes/{id}/delete',  [ClassWebController::class, 'destroy'], $both);

// ── Subjects ──────────────────────────────────────────────────────────────────
$router->get('/subjects',              [SubjectWebController::class, 'index'],   $auth);
$router->get('/subjects/create',       [SubjectWebController::class, 'create'],  $auth);
$router->post('/subjects',             [SubjectWebController::class, 'store'],   $both);
$router->get('/subjects/{id}',         [SubjectWebController::class, 'show'],    $auth);
$router->get('/subjects/{id}/edit',    [SubjectWebController::class, 'edit'],    $auth);
$router->post('/subjects/{id}/update', [SubjectWebController::class, 'update'],  $both);
$router->post('/subjects/{id}/delete', [SubjectWebController::class, 'destroy'], $both);

// ── Academic Years ────────────────────────────────────────────────────────────
$router->get('/academic-years',                   [AcademicYearController::class, 'index'],      $auth);
$router->get('/academic-years/create',            [AcademicYearController::class, 'create'],     $auth);
$router->post('/academic-years',                  [AcademicYearController::class, 'store'],      $both);
$router->post('/academic-years/{id}/set-current', [AcademicYearController::class, 'setCurrent'], $both);
$router->post('/academic-years/{id}/delete',      [AcademicYearController::class, 'destroy'],    $both);

// ── Terms ─────────────────────────────────────────────────────────────────────
$router->get('/terms',               [TermController::class, 'index'],      $auth);
$router->get('/terms/create',        [TermController::class, 'create'],     $auth);
$router->post('/terms',              [TermController::class, 'store'],      $both);
$router->get('/terms/{id}/edit',     [TermController::class, 'edit'],       $auth);
$router->post('/terms/{id}/update',  [TermController::class, 'update'],     $both);
$router->post('/terms/{id}/current', [TermController::class, 'setCurrent'], $both);
$router->post('/terms/{id}/delete',  [TermController::class, 'destroy'],    $both);

// ── Parents ───────────────────────────────────────────────────────────────────
$router->get('/parents',               [ParentsWebController::class, 'index'],        $auth);
$router->get('/parents/create',        [ParentsWebController::class, 'create'],       $auth);
$router->post('/parents',              [ParentsWebController::class, 'store'],         $both);
$router->get('/parents/{id}',          [ParentsWebController::class, 'show'],         $auth);
$router->get('/profile',               [ParentsWebController::class, 'profile'],      $auth);
$router->post('/profile',              [ParentsWebController::class, 'updateProfile'], $both);
$router->post('/parents/link-student', [ParentsWebController::class, 'linkStudent'],  $both);

// ── Finance ───────────────────────────────────────────────────────────────────
$router->get('/finance',                       [FinanceWebController::class, 'index'],            $auth);
$router->get('/finance/fee-types/create',      [FinanceWebController::class, 'createFeeType'],    $auth);
$router->post('/finance/fee-types',            [FinanceWebController::class, 'storeFeeType'],     $both);
$router->get('/finance/statement/{studentId}', [FinanceWebController::class, 'statement'],        $auth);
$router->post('/finance/payments',             [FinanceWebController::class, 'recordPayment'],    $both);
$router->post('/finance/invoices/generate',    [FinanceWebController::class, 'generateInvoices'], $both);

// ── Exams ────────────────────────────────────────────────────────────────────
$router->get('/exams',                           [ExamWebController::class, 'index'],            $auth);
$router->get('/exams/create',                    [ExamWebController::class, 'create'],           $auth);
$router->post('/exams',                          [ExamWebController::class, 'store'],            $both);
$router->get('/exams/grade-scales',              [ExamWebController::class, 'gradeScales'],      $auth);
$router->post('/exams/grade-scales',             [ExamWebController::class, 'storeGradeScale'],  $both);
$router->post('/exams/grade-scales/{id}/delete', [ExamWebController::class, 'deleteGradeScale'], $both);
$router->get('/exams/{id}',                      [ExamWebController::class, 'show'],             $auth);
$router->post('/exams/{id}/publish',            [ExamWebController::class, 'publish'],           $both);
$router->get('/exams/{id}/results',              [ExamWebController::class, 'results'],          $auth);
$router->post('/exams/{id}/results',             [ExamWebController::class, 'submitResults'],    $both);
$router->post('/exams/{id}/delete',              [ExamWebController::class, 'destroy'],          $both);

// ── Attendance ────────────────────────────────────────────────────────────────
$router->get('/attendance',         [AttendanceController::class, 'index'],   $auth);
$router->post('/attendance',        [AttendanceController::class, 'mark'],    $both);
$router->get('/attendance/summary', [AttendanceController::class, 'summary'], $auth);

// ── Discipline ────────────────────────────────────────────────────────────────
$router->get('/discipline',              [DisciplineController::class, 'index'],   $auth);
$router->get('/discipline/create',       [DisciplineController::class, 'create'],  $auth);
$router->post('/discipline',             [DisciplineController::class, 'store'],   $both);
$router->post('/discipline/{id}/delete', [DisciplineController::class, 'destroy'], $both);

// ── Notices ───────────────────────────────────────────────────────────────────
$router->get('/notices',              [NoticeController::class, 'index'],   $auth);
$router->get('/notices/create',       [NoticeController::class, 'create'],  $auth);
$router->post('/notices',             [NoticeController::class, 'store'],   $both);
$router->get('/notices/{id}',         [NoticeController::class, 'show'],    $auth);
$router->post('/notices/{id}/delete', [NoticeController::class, 'destroy'], $both);

// ── Reports ───────────────────────────────────────────────────────────────────
$router->get('/reports/class-results',                    [ReportsWebController::class, 'classResults'],      $auth);
$router->get('/reports/subject-performance',              [ReportsWebController::class, 'subjectPerformance'],$auth);
$router->get('/reports/attendance-summary',               [ReportsWebController::class, 'attendanceSummary'], $auth);
$router->get('/reports/fee-collection',                   [ReportsWebController::class, 'feeCollection'], $auth);
$router->get('/reports/report-card/{studentId}',           [ReportsWebController::class, 'reportCard'],     $auth);
$router->post('/reports/report-card/{studentId}/remarks', [ReportsWebController::class, 'updateRemarks'],  $both);

// ── Users ─────────────────────────────────────────────────────────────────────
$router->get('/users',                      [UserController::class, 'index'],         $auth);
$router->get('/users/create',               [UserController::class, 'create'],        $auth);
$router->post('/users',                     [UserController::class, 'store'],         $both);
$router->get('/users/{id}',                 [UserController::class, 'show'],          $auth);
$router->post('/users/{id}/reset-password', [UserController::class, 'resetPassword'], $both);
$router->post('/users/{id}/{action}',       [UserController::class, 'toggleActive'],  $both);

// ── Settings ──────────────────────────────────────────────────────────────────
$router->get('/settings',            [SettingsWebController::class, 'index'],      $auth);
$router->get('/settings/edit',       [SettingsWebController::class, 'edit'],       $auth);
$router->post('/settings/edit',      [SettingsWebController::class, 'update'],     $both);
$router->get('/settings/school',     [SettingsWebController::class, 'school'],     $auth);
$router->get('/settings/logo',       [SettingsWebController::class, 'logo'],       $auth);
$router->post('/settings/logo',      [SettingsWebController::class, 'updateLogo'], $both);

// ── Public marketing site ───────────────────────────────────────────────────
// This is the ONLY '/' registration in the file now — it was previously
// registered three times (an old "redirect to /dashboard" closure, an
// accidental duplicate of this exact line, and this one). Since Router::
// dispatch() matches in registration order and stops at the first hit,
// whichever '/' route appears FIRST in the file wins, permanently, and the
// other two are dead code. That's why every visit landed on /login: the old
// closure redirected to /dashboard, which is $auth-gated, which bounced to
// /login. Keep exactly one GET '/' route, and keep it defined here so
// login/dashboard/etc. above it fail closed if something's misconfigured.
$router->get('/',              [MarketingController::class, 'home'],        $guest);
$router->get('/pricing',       [MarketingController::class, 'pricing'],     $guest);
$router->get('/demo',          [MarketingController::class, 'demoForm'],    $guest);
$router->post('/demo',         [MarketingController::class, 'submitDemo'],  $csrfOnly);
$router->get('/demo/thank-you',[MarketingController::class, 'demoThankYou'],$guest);

// ── Super admin: demo request review queue ──────────────────────────────────
$router->get('/super-admin/demo-requests',                 [SuperAdminDemoRequestsController::class, 'index'],         $auth);
$router->get('/super-admin/demo-requests/{id}',            [SuperAdminDemoRequestsController::class, 'show'],          $auth);
$router->post('/super-admin/demo-requests/{id}/contacted', [SuperAdminDemoRequestsController::class, 'markContacted'], $both);
$router->post('/super-admin/demo-requests/{id}/decline',   [SuperAdminDemoRequestsController::class, 'decline'],       $both);
$router->post('/super-admin/demo-requests/{id}/approve',   [SuperAdminDemoRequestsController::class, 'approve'],       $both);

// ── Super admin: tenants ─────────────────────────────────────────────────────
$router->get('/super-admin/tenants',                   [SuperAdminController::class, 'index'],       $auth);
$router->get('/super-admin/tenants/create',            [SuperAdminController::class, 'create'],      $auth);
$router->post('/super-admin/tenants',                  [SuperAdminController::class, 'store'],       $both);
$router->get('/super-admin/tenants/{id}/provisioned',  [SuperAdminController::class, 'provisioned'], $auth);
$router->post('/super-admin/tenants/{id}/suspend',     [SuperAdminController::class, 'suspend'],     $both);
$router->post('/super-admin/tenants/{id}/reactivate',  [SuperAdminController::class, 'reactivate'],  $both);
$router->get('/activity-log',              [AuditLogWebController::class, 'index'],    $auth);
$router->get('/super-admin/activity-log',  [AuditLogWebController::class, 'platform'], $auth);

// ── Tenant-facing billing (read-only) ───────────────────────────────────────
$router->get('/billing', [BillingWebController::class, 'index'], $auth);

// ── Super admin billing ──────────────────────────────────────────────────────
$router->get('/super-admin/billing',                            [SuperAdminBillingController::class, 'index'],              $auth);
$router->get('/super-admin/billing/tenants/{id}',               [SuperAdminBillingController::class, 'tenantDetail'],       $auth);
$router->post('/super-admin/billing/tenants/{id}/subscribe',    [SuperAdminBillingController::class, 'subscribe'],          $both);
$router->post('/super-admin/billing/invoices/{id}/pay',         [SuperAdminBillingController::class, 'recordPayment'],      $both);
$router->post('/super-admin/billing/subscriptions/{id}/cancel', [SuperAdminBillingController::class, 'cancelSubscription'], $both);

// ── Future async gateway webhooks (uncomment once a real gateway is wired) ──
// These must NOT go through the normal $auth middleware — gateways call them
// directly, not a logged-in browser. Verify the request instead via gateway-
// specific signature/IP checks inside the handler.
// $router->post('/billing/webhooks/mpesa',  [MpesaWebhookController::class, 'handle']);
// $router->post('/billing/webhooks/stripe', [StripeWebhookController::class, 'handle']);
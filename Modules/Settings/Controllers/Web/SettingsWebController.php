<?php

declare(strict_types=1);

namespace Modules\Settings\Controllers\Web;

use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Settings\Repositories\SettingsRepository;
use Modules\Settings\Services\SettingsService;

final class SettingsWebController extends WebController
{
    private SettingsService $svc;

    public function __construct()
    {
        $this->svc = new SettingsService(new SettingsRepository());
        $this->hydrateContext();
    }

    private function hydrateContext(): void
    {
        $user = Session::user() ?? [];
        RequestContext::set([
            'user_id'   => $user['id'] ?? 0,
            'tenant_id' => Session::get('tenant_id', 0),
            'school_id' => Session::get('school_id'),
            'roles'     => Session::get('roles', []),
        ]);
    }

    public function index(array $params = []): void
    {
        $this->requirePermission('settings.manage');

        $schoolId = RequestContext::schoolId();
        if (!$schoolId) {
            $this->forbidden();
            return;
        }

        $this->view('settings/index', [
            'title'    => 'School Settings',
            'school'   => $this->svc->getSchool($schoolId),
            'settings' => $this->svc->getSchoolSettings($schoolId),
        ]);
    }

    public function edit(array $params = []): void
    {
        $this->requirePermission('settings.manage');

        $schoolId = RequestContext::schoolId();
        if (!$schoolId) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('settings/edit', [
            'title'    => 'Edit School Settings',
            'school'   => $this->svc->getSchool($schoolId),
            'settings' => $this->svc->getSchoolSettings($schoolId),
        ]);
    }

    public function update(array $params = []): void
    {
        $this->requirePermission('settings.manage');

        $schoolId = RequestContext::schoolId();
        if (!$schoolId) {
            $this->redirect('/dashboard');
            return;
        }

        try {
            $this->svc->updateSchool($schoolId, [
                'name'           => trim($_POST['name']           ?? ''),
                'address'        => trim($_POST['address']        ?? ''),
                'phone'          => trim($_POST['phone']          ?? ''),
                'email'          => trim($_POST['email']          ?? ''),
                'motto'          => trim($_POST['motto']          ?? ''),
                'county'         => trim($_POST['county']         ?? ''),
                'sub_county'     => trim($_POST['sub_county']     ?? ''),
                'knec_code'      => trim($_POST['knec_code']      ?? ''),
                'school_type'    => $_POST['school_type']         ?? 'day',
                'school_level'   => $_POST['school_level']        ?? 'secondary',
                'principal_name' => trim($_POST['principal_name'] ?? ''),
                'mpesa_paybill'  => trim($_POST['mpesa_paybill']  ?? ''),
                'mpesa_account'  => trim($_POST['mpesa_account']  ?? ''),
                'website'        => trim($_POST['website']        ?? ''),
            ]);

            $this->svc->updateSchoolSettings($schoolId, [
                'admission_prefix'    => trim($_POST['admission_prefix']    ?? 'SCH'),
                'admission_year_mode' => $_POST['admission_year_mode']      ?? 'academic_year',
                'admission_next'      => (int)($_POST['admission_next']     ?? 1),
                'admission_padding'   => (int)($_POST['admission_padding']  ?? 4),
            ]);

            $this->redirect('/settings', 'Settings updated.');
        } catch (\Throwable $e) {
            $this->view('settings/edit', [
                'title'    => 'Edit School Settings',
                'error'    => $e->getMessage(),
                'school'   => $this->svc->getSchool($schoolId),
                'settings' => $_POST,
            ]);
        }
    }

    public function school(array $params = []): void
    {
        $this->requirePermission('settings.manage');

        $schoolId = RequestContext::schoolId();
        if (!$schoolId) {
            $this->forbidden();
            return;
        }

        $this->view('settings/school', [
            'title'  => 'School Profile',
            'school' => $this->svc->getSchool($schoolId),
        ]);
    }

    public function logo(array $params = []): void
    {
        $this->requirePermission('settings.manage');

        $schoolId = RequestContext::schoolId();
        if (!$schoolId) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('settings/logo', [
            'title'  => 'School Logo',
            'school' => $this->svc->getSchool($schoolId),
        ]);
    }

    public function updateLogo(array $params = []): void
    {
        $this->requirePermission('settings.manage');

        $schoolId = RequestContext::schoolId();
        if (!$schoolId) {
            $this->redirect('/dashboard');
            return;
        }

        $logoUrl = trim($_POST['logo_url'] ?? '');
        if ($logoUrl === '') {
            $this->view('settings/logo', [
                'title'  => 'School Logo',
                'error'  => 'Logo URL is required.',
                'school' => $this->svc->getSchool($schoolId),
            ]);
            return;
        }

        try {
            $this->svc->updateLogo($schoolId, $logoUrl);
            $this->redirect('/settings', 'Logo updated.');
        } catch (\Throwable $e) {
            $this->view('settings/logo', [
                'title'  => 'School Logo',
                'error'  => $e->getMessage(),
                'school' => $this->svc->getSchool($schoolId),
            ]);
        }
    }

    private function forbidden(): void
    {
        http_response_code(403);
        $this->view('errors/403', [
            'title'   => 'Access Denied',
            'message' => 'No school context available.',
        ]);
    }
}
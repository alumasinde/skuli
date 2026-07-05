<?php
declare(strict_types=1);

namespace Modules\Tenants\Services;

use Core\AuditLogger;
use Modules\Tenants\Repositories\TenantRepository;
use Core\Env;


/**
 * TenantProvisioningService — the "manual tenant creation after a successful
 * demo" workflow. One call creates: a tenant row, its first school, and an
 * admin user who can log in immediately. This is the seam future self-service
 * signup (Phase 3) will call into as well — signup becomes just another
 * caller of provision(), with payment/trial logic wrapped around it instead
 * of duplicating this logic.
 */
final class TenantProvisioningService
{
    public function __construct(
        private readonly TenantRepository $repo,
        private readonly AuditLogger $audit
    ) {}

    /**
     * @param array{
     *   tenant_name: string, plan?: string, domain?: string, notes?: string,
     *   school_name: string, school_code: string, school_email?: string, school_phone?: string,
     *   admin_first_name: string, admin_last_name: string, admin_email: string,
     * } $d
     * @return array{tenant_id:int, school_id:int, admin_user_id:int, temp_password:string}
     */
    public function provision(array $d, ?int $createdByUserId): array
    {
        $this->validate($d);

        $slug = $this->slugify($d['tenant_name']);
        if ($this->repo->slugExists($slug)) {
            $slug .= '-' . bin2hex(random_bytes(2));
            $domain = trim($d['domain'] ?? '') ?: ($slug . '.' . Env::get('APP_BASE_DOMAIN', 'easyschools.com'));

        }

        $tenantId = $this->repo->create([
            'slug'       => $slug,
            'name'       => trim($d['tenant_name']),
            'domain'     => $domain ?? null,
            'plan'       => $d['plan'] ?? 'free',
            'status'     => 'active',
            'notes'      => $d['notes'] ?? null,
            'created_by' => $createdByUserId,
        ]);

        $schoolId = $this->repo->createSchoolForTenant($tenantId, [
            'name'         => trim($d['school_name']),
            'code'         => strtoupper(trim($d['school_code'])),
            'email'        => $d['school_email'] ?? null,
            'phone'        => $d['school_phone'] ?? null,
            'school_type'  => $d['school_type'] ?? 'day',
            'school_level' => $d['school_level'] ?? 'secondary',
        ]);
        $this->repo->seedSchoolSettings($schoolId);

        // Generate a temporary password rather than letting the super admin
        // choose one — it's shown once, and the admin should change it on
        // first login. Never logged or stored in plaintext.
        $tempPassword = $this->generateTempPassword();

        $adminUserId = $this->repo->createAdminUser($tenantId, $schoolId, [

            'first_name' => trim($d['admin_first_name']),
            'last_name'  => trim($d['admin_last_name']),
            'email'      => strtolower(trim($d['admin_email'])),
            'password_hash' => password_hash($tempPassword, PASSWORD_DEFAULT),
        ]);

        $adminRoleId = $this->repo->findGlobalRoleIdByCode('admin');
if ($adminRoleId === null) {
    throw new \RuntimeException(
        "No global 'admin' role found in the roles table. Seed it before provisioning tenants."
    );
}
$this->repo->linkUserToRole($adminUserId, $adminRoleId);

        $this->audit->log('provision', 'tenant', $tenantId, [
            'school_id'      => $schoolId,
            'admin_user_id'  => $adminUserId,
            'admin_email'    => $d['admin_email'],
        ]);

        return [
            'tenant_id'     => $tenantId,
            'school_id'     => $schoolId,
            'admin_user_id' => $adminUserId,
            'temp_password' => $tempPassword,
        ];
    }

    public function list(): array
    {
        return $this->repo->listAll();
    }

    public function getById(int $id): ?array
    {
        return $this->repo->findById($id);
    }

    public function suspend(int $id): void
    {
        $this->repo->updateStatus($id, 'suspended');
        $this->audit->log('suspend', 'tenant', $id);
    }

    public function reactivate(int $id): void
    {
        $this->repo->updateStatus($id, 'active');
        $this->audit->log('reactivate', 'tenant', $id);
    }

    private function validate(array $d): void
    {
        foreach (['tenant_name', 'school_name', 'school_code', 'admin_first_name', 'admin_last_name', 'admin_email'] as $field) {
            if (trim((string) ($d[$field] ?? '')) === '') {
                throw new \InvalidArgumentException("Field \"{$field}\" is required.");
            }
        }
        if (!filter_var($d['admin_email'], FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Admin email is not valid.');
        }
    }

    private function slugify(string $name): string
    {
        $slug = strtolower(trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-'));
        return $slug !== '' ? $slug : bin2hex(random_bytes(4));
    }

    /** Human-typeable temp password: e.g. "Correct-Horse-42". Not for long-term use. */
    private function generateTempPassword(): string
    {
        $words = ['Amber', 'Cedar', 'Delta', 'Ember', 'Falcon', 'Granite', 'Harbor', 'Ivory', 'Jasper', 'Kestrel'];
        return $words[array_rand($words)] . '-' . random_int(1000, 9999) . '!';
    }
}

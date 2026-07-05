<?php
declare(strict_types=1);

namespace Tests\Unit;

/**
 * ─────────────────────────────────────────────────────────────────────────
 * REQUIRES ONE CHANGE BEFORE THIS RUNS: AuthRepository and AuditLogger are
 * both declared `final class ...`. PHPUnit's createMock() generates a test
 * double by subclassing the real class — PHP will not let you subclass a
 * final class, full stop, no PHPUnit config works around this. Remove the
 * `final` keyword from just those two class declarations to make this test
 * runnable. That's the entire change needed — nothing else about either
 * class needs to differ.
 *
 * This is a real, deliberate tradeoff, not an oversight: `final` buys you a
 * guarantee nobody accidentally subclasses these; testability wants the
 * opposite. For AuthRepository/AuditLogger specifically, testability wins —
 * these are exactly the "highest-consequence code" worth having a real
 * safety net on.
 *
 * Also note: Jwt::issue()/Jwt::verify() are called for real in the success
 * path below — static calls can't be mocked by standard PHPUnit doubles.
 * That means this test needs a real JWT_SECRET available; setUp() sets one
 * via putenv() so this test doesn't depend on your actual .env.
 * ─────────────────────────────────────────────────────────────────────────
 */

use Core\AuditLogger;
use Modules\Auth\Repositories\AuthRepository;
use Modules\Auth\Services\AuthService;
use PHPUnit\Framework\TestCase;

final class AuthServiceTest extends TestCase
{
    protected function setUp(): void
    {
        // Jwt::issue() needs this — set here so the test is self-contained
        // and doesn't depend on a real .env being present in the test run.
        if (getenv('JWT_SECRET') === false) {
            putenv('JWT_SECRET=' . bin2hex(random_bytes(32)));
        }
    }

    private function activeUserRow(): array
    {
        return [
            'id' => 42,
            'tenant_id' => 7,
            'school_id' => 3,
            'first_name' => 'Albert',
            'last_name' => 'Masinde',
            'email' => 'albert@example.com',
            'password_hash' => password_hash('correct-password', PASSWORD_DEFAULT),
            'is_active' => 1,
        ];
    }

    public function test_login_fails_when_no_user_matches_and_audits_it(): void
    {
        $repo = $this->createMock(AuthRepository::class);
        $repo->method('findUserByEmailAndTenant')->willReturn(null);

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects($this->once())
            ->method('log')
            ->with(
                'login_failed',
                'auth',
                null,
                $this->callback(fn ($meta) => $meta['reason'] === 'user_not_found'),
                tenantId: 7,
                schoolId: 0,
                actorId: null
            );

        $service = new AuthService($repo, $audit);
        $result  = $service->login('nobody@example.com', 'whatever', 7);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid credentials.', $result['error']);
    }

    public function test_login_fails_when_account_is_inactive_and_audits_it(): void
    {
        $user = $this->activeUserRow();
        $user['is_active'] = 0;

        $repo = $this->createMock(AuthRepository::class);
        $repo->method('findUserByEmailAndTenant')->willReturn($user);

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects($this->once())
            ->method('log')
            ->with(
                'login_failed', 'auth', 42,
                $this->callback(fn ($meta) => $meta['reason'] === 'inactive_account'),
                tenantId: 7, schoolId: 3, actorId: 42
            );

        $service = new AuthService($repo, $audit);
        $result  = $service->login('albert@example.com', 'correct-password', 7);

        $this->assertFalse($result['success']);
        $this->assertSame('Account disabled.', $result['error']);
    }

    public function test_login_fails_on_wrong_password_and_audits_it(): void
    {
        $repo = $this->createMock(AuthRepository::class);
        $repo->method('findUserByEmailAndTenant')->willReturn($this->activeUserRow());

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects($this->once())
            ->method('log')
            ->with(
                'login_failed', 'auth', 42,
                $this->callback(fn ($meta) => $meta['reason'] === 'bad_password'),
                tenantId: 7, schoolId: 3, actorId: 42
            );

        $service = new AuthService($repo, $audit);
        $result  = $service->login('albert@example.com', 'totally-wrong', 7);

        $this->assertFalse($result['success']);
        $this->assertSame('Invalid credentials.', $result['error']);
    }

    public function test_successful_login_returns_tokens_and_audits_success(): void
    {
        $user = $this->activeUserRow();

        $repo = $this->createMock(AuthRepository::class);
        $repo->method('findUserByEmailAndTenant')->willReturn($user);
        $repo->method('findRoleCodesByUserId')->willReturn(['admin']);
        $repo->method('findPermissionsByRoleCodes')->willReturn(['students.view', 'students.create']);
        $repo->method('getCurrentAcademicYearId')->willReturn(11);
        $repo->method('getCurrentTermId')->willReturn(101);
        // updatePassword/updateLastLogin are void — no return value to stub,
        // just need to not throw when called.

        $audit = $this->createMock(AuditLogger::class);
        $audit->expects($this->once())
            ->method('log')
            ->with(
                'login_success', 'auth', 42,
                $this->callback(fn ($meta) => $meta['roles'] === ['admin']),
                tenantId: 7, schoolId: 3, actorId: 42
            );

        $service = new AuthService($repo, $audit);
        $result  = $service->login('albert@example.com', 'correct-password', 7);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('access_token', $result['data']);
        $this->assertArrayHasKey('refresh_token', $result['data']);
        $this->assertSame('Albert', $result['data']['user']['first_name']);
        $this->assertSame(['admin'], $result['data']['roles']);
        $this->assertSame(11, $result['data']['context']['academic_year_id']);
    }
}

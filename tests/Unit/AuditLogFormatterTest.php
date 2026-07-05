<?php
declare(strict_types=1);

namespace Tests\Unit;

use Core\AuditLogFormatter;
use PHPUnit\Framework\TestCase;

/**
 * This test exists specifically to verify, by actually running it, the
 * date-formatting reasoning that earlier had to be worked through by hand
 * (no PHP binary was available in that sandbox to confirm it directly).
 * Run this for real — `composer test` — before trusting the format.
 */
final class AuditLogFormatterTest extends TestCase
{
    public function test_login_success_sentence_matches_expected_format(): void
    {
        $row = [
            'action'           => 'login_success',
            'entity'           => 'auth',
            'actor_first_name' => 'Albert',
            'actor_last_name'  => 'Masinde',
            'meta'             => null,
            'created_at'       => '2026-07-04 08:20:03',
        ];

        $sentence = AuditLogFormatter::format($row);

        $this->assertSame(
            'Albert Masinde Logged In at 08:20:03 am on 04/07/2026',
            $sentence
        );
    }

    public function test_pm_time_formats_correctly(): void
    {
        $row = [
            'action' => 'login_success', 'entity' => 'auth',
            'actor_first_name' => 'Jane', 'actor_last_name' => 'Doe',
            'meta' => null, 'created_at' => '2026-07-04 23:05:00',
        ];

        // Confirms 24h -> 12h conversion and lowercase am/pm are both correct —
        // this is the exact detail that's easy to get backwards (11:05:00 pm,
        // not 23:05:00 or 11:05:00 PM).
        $this->assertStringContainsString('11:05:00 pm on 04/07/2026', AuditLogFormatter::format($row));
    }

    public function test_create_action_appends_humanized_entity_name(): void
    {
        $row = [
            'action' => 'create', 'entity' => 'demo_request',
            'actor_first_name' => 'Albert', 'actor_last_name' => 'Masinde',
            'meta' => null, 'created_at' => '2026-07-04 08:20:03',
        ];

        $this->assertSame(
            'Albert Masinde Created Demo Request at 08:20:03 am on 04/07/2026',
            AuditLogFormatter::format($row)
        );
    }

    public function test_login_actions_never_append_the_entity_name(): void
    {
        // "Logged In Auth" would read wrong — OMIT_ENTITY exists specifically
        // to prevent this for login_success/login_failed/logout.
        $row = [
            'action' => 'login_failed', 'entity' => 'auth',
            'actor_first_name' => 'Albert', 'actor_last_name' => 'Masinde',
            'meta' => null, 'created_at' => '2026-07-04 08:20:03',
        ];

        $sentence = AuditLogFormatter::format($row);
        $this->assertStringNotContainsString('Auth', $sentence);
        $this->assertSame('Albert Masinde Failed to Log In at 08:20:03 am on 04/07/2026', $sentence);
    }

    public function test_falls_back_to_email_from_meta_when_no_actor_linked(): void
    {
        // The user-not-found case: actor_id is null, so there's no joined
        // users row — actor_first_name/last_name will both be null/absent.
        $row = [
            'action' => 'login_failed', 'entity' => 'auth',
            'actor_first_name' => null, 'actor_last_name' => null,
            'meta' => json_encode(['reason' => 'user_not_found', 'email' => 'ghost@example.com']),
            'created_at' => '2026-07-04 08:20:03',
        ];

        $this->assertStringStartsWith('ghost@example.com Failed to Log In', AuditLogFormatter::format($row));
    }

    public function test_falls_back_to_someone_when_no_actor_and_no_email(): void
    {
        $row = [
            'action' => 'create', 'entity' => 'student',
            'actor_first_name' => null, 'actor_last_name' => null,
            'meta' => null, 'created_at' => '2026-07-04 08:20:03',
        ];

        $this->assertStringStartsWith('Someone Created Student', AuditLogFormatter::format($row));
    }

    public function test_unknown_action_gets_a_readable_fallback_phrase(): void
    {
        // Any action not in ACTION_PHRASES yet shouldn't crash — it should
        // degrade to a readable guess rather than throwing.
        $row = [
            'action' => 'archive_bulk_export', 'entity' => 'report',
            'actor_first_name' => 'Albert', 'actor_last_name' => 'Masinde',
            'meta' => null, 'created_at' => '2026-07-04 08:20:03',
        ];

        $this->assertStringContainsString('Archive bulk export Report', AuditLogFormatter::format($row));
    }
}

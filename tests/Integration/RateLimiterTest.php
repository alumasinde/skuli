<?php
declare(strict_types=1);

namespace Tests\Integration;


use Core\RateLimiter;
use PHPUnit\Framework\TestCase;
use PDO;
use PDOException;

final class RateLimiterTest extends TestCase
{
    private ?PDO $db = null;

    protected function setUp(): void
    {
        try {
            $host = getenv('TEST_DB_HOST') ?: '127.0.0.1';
            $name = getenv('TEST_DB_DATABASE') ?: 'ssms_test';
            $user = getenv('TEST_DB_USERNAME') ?: 'root';
            $pass = getenv('TEST_DB_PASSWORD') ?: '';

            $this->db = new PDO(
                "mysql:host={$host};dbname={$name};charset=utf8mb4",
                $user,
                $pass,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );

            // Clean slate for every test — this table should ONLY ever
            // contain rows this test suite created.
            $this->db->exec('DELETE FROM rate_limits');
        } catch (PDOException $e) {
            $this->markTestSkipped(
                'No test database reachable (' . $e->getMessage() . '). '
                . 'Set TEST_DB_* env vars and run db/008_rate_limits.sql against it to enable this test.'
            );
        }
    }

    public function test_allows_requests_under_the_limit(): void
    {
        $limiter = new RateLimiter($this->db);

        for ($i = 0; $i < 5; $i++) {
            $this->assertFalse(
                $limiter->tooManyAttempts('test:1.2.3.4', 5, 60),
                "Attempt #{$i} should not have been blocked yet"
            );
        }
    }

    public function test_blocks_once_over_the_limit(): void
    {
        $limiter = new RateLimiter($this->db);

        for ($i = 0; $i < 5; $i++) {
            $limiter->tooManyAttempts('test:5.6.7.8', 5, 60);
        }

        // The 6th call should push it over.
        $this->assertTrue($limiter->tooManyAttempts('test:5.6.7.8', 5, 60));
    }

    public function test_different_keys_do_not_interfere_with_each_other(): void
    {
        $limiter = new RateLimiter($this->db);

        for ($i = 0; $i < 5; $i++) {
            $limiter->tooManyAttempts('test:ip-a', 5, 60);
        }

        // A different bucket should be completely unaffected.
        $this->assertFalse($limiter->tooManyAttempts('test:ip-b', 5, 60));
    }

    public function test_clear_resets_a_bucket(): void
    {
        $limiter = new RateLimiter($this->db);

        for ($i = 0; $i < 5; $i++) {
            $limiter->tooManyAttempts('test:clearable', 5, 60);
        }
        $this->assertTrue($limiter->tooManyAttempts('test:clearable', 5, 60));

        $limiter->clear('test:clearable');

        $this->assertFalse($limiter->tooManyAttempts('test:clearable', 5, 60));
    }

    public function test_window_expiry_resets_the_counter(): void
    {
        $limiter = new RateLimiter($this->db);

        for ($i = 0; $i < 5; $i++) {
            $limiter->tooManyAttempts('test:expiring', 5, 60);
        }
        $this->assertTrue($limiter->tooManyAttempts('test:expiring', 5, 60));

        // Simulate the window having already expired by back-dating the
        // row directly, rather than sleeping the test for real seconds.
        $this->db->prepare(
            'UPDATE rate_limits SET window_started_at = (NOW() - INTERVAL 120 SECOND) WHERE bucket_key = ?'
        )->execute(['test:expiring']);

        $this->assertFalse(
            $limiter->tooManyAttempts('test:expiring', 5, 60),
            'A 60s window that started 120s ago should have reset the counter'
        );
    }
}

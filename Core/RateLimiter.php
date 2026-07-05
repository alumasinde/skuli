<?php
declare(strict_types=1);

namespace Core;

final class RateLimiter
{
    private \PDO $db;

    public function __construct(?\PDO $db = null)
    {
        $this->db = $db ?? Database::connection();
    }

    /**
     * Records this attempt against $key and returns true if it pushed the
     * bucket over $maxAttempts within $windowSeconds. Call this once per
     * request you want rate-limited — every call counts as an attempt.
     */
    public function tooManyAttempts(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        return $this->hit($key, $windowSeconds) > $maxAttempts;
    }

    /** How many attempts remain before the next call would be blocked. */
    public function remaining(string $key, int $maxAttempts): int
    {
        $stmt = $this->db->prepare('SELECT attempts FROM rate_limits WHERE bucket_key = ?');
        $stmt->execute([$key]);
        $attempts = (int) ($stmt->fetchColumn() ?: 0);
        return max(0, $maxAttempts - $attempts);
    }

    /** Seconds until this bucket's window resets, for a "try again in Ns" message. */
    public function availableInSeconds(string $key, int $windowSeconds): int
    {
        $stmt = $this->db->prepare('SELECT window_started_at FROM rate_limits WHERE bucket_key = ?');
        $stmt->execute([$key]);
        $startedAt = $stmt->fetchColumn();
        if (!$startedAt) {
            return 0;
        }
        $elapsed = time() - strtotime((string) $startedAt);
        return max(0, $windowSeconds - $elapsed);
    }

    private function hit(string $key, int $windowSeconds): int
    {
        $stmt = $this->db->prepare('
            INSERT INTO rate_limits (bucket_key, attempts, window_started_at)
            VALUES (?, 1, NOW())
            ON DUPLICATE KEY UPDATE
                attempts = IF(window_started_at < (NOW() - INTERVAL ? SECOND), 1, attempts + 1),
                window_started_at = IF(window_started_at < (NOW() - INTERVAL ? SECOND), NOW(), window_started_at)
        ');
        $stmt->execute([$key, $windowSeconds, $windowSeconds]);

        $select = $this->db->prepare('SELECT attempts FROM rate_limits WHERE bucket_key = ?');
        $select->execute([$key]);
        return (int) $select->fetchColumn();
    }

    /** Manually clear a bucket — e.g. after a successful login, so a prior
     *  near-miss doesn't count against the user's next legitimate attempt. */
    public function clear(string $key): void
    {
        $stmt = $this->db->prepare('DELETE FROM rate_limits WHERE bucket_key = ?');
        $stmt->execute([$key]);
    }
}

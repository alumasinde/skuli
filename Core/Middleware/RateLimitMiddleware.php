<?php
declare(strict_types=1);

namespace Core\Middleware;

use Core\Contracts\Middleware;
use Core\Http;
use Core\RateLimiter;
use Core\Response;

final class RateLimitMiddleware implements Middleware
{
    private RateLimiter $limiter;

    public function __construct(private readonly string $scope)
    {
        $this->limiter = new RateLimiter();
    }

    public function handle(array &$context, callable $next): void
    {
        $prefix = strtoupper($this->scope); // 'AUTH' or 'API'
        $max    = (int) (\Core\Env::get("RATE_LIMIT_{$prefix}", 10));
        $window = (int) (\Core\Env::get("RATE_LIMIT_{$prefix}_WINDOW", 60));

        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = "{$this->scope}:{$ip}";

        if ($this->limiter->tooManyAttempts($key, $max, $window)) {
            $retryAfter = $this->limiter->availableInSeconds($key, $window);
            header("Retry-After: {$retryAfter}");
            Response::error(
                "Too many attempts. Please try again in {$retryAfter} seconds.",
                Http::TOO_MANY_REQUESTS
            );
            return;
        }

        $next();
    }
}

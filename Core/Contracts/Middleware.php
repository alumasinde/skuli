<?php
declare(strict_types=1);

namespace Core\Contracts;

interface Middleware
{
    /**
     * @param array    $context Mutable request context (params, auth user, etc.)
     * @param callable $next    Call to continue the chain; don't call it to halt.
     */
    public function handle(array &$context, callable $next): void;
}

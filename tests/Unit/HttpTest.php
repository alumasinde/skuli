<?php
declare(strict_types=1);

namespace Tests\Unit;

use Core\Http;
use PHPUnit\Framework\TestCase;

final class HttpTest extends TestCase
{
    public function test_status_values_match_the_real_http_spec(): void
    {
        $this->assertSame(200, Http::OK->value);
        $this->assertSame(201, Http::CREATED->value);
        $this->assertSame(400, Http::BAD_REQUEST->value);
        $this->assertSame(401, Http::UNAUTHORIZED->value);
        $this->assertSame(403, Http::FORBIDDEN->value);
        $this->assertSame(404, Http::NOT_FOUND->value);
        $this->assertSame(429, Http::TOO_MANY_REQUESTS->value);
        $this->assertSame(500, Http::SERVER_ERROR->value);
    }

    public function test_is_success_is_only_true_for_2xx(): void
    {
        $this->assertTrue(Http::OK->isSuccess());
        $this->assertTrue(Http::CREATED->isSuccess());
        $this->assertFalse(Http::BAD_REQUEST->isSuccess());
        $this->assertFalse(Http::SERVER_ERROR->isSuccess());
    }

    public function test_label_gives_the_standard_reason_phrase(): void
    {
        $this->assertSame('Not Found', Http::NOT_FOUND->label());
        $this->assertSame('Too Many Requests', Http::TOO_MANY_REQUESTS->label());
    }
}

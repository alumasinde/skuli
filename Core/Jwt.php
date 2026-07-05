<?php
declare(strict_types=1);

namespace Core;

/**
 * Jwt — minimal HS256 JWT issue/verify. No firebase/php-jwt dependency;
 * this is a deliberately small, auditable implementation since auth is
 * security-critical and we want zero hidden behaviour.
 *
 * Claims structure intentionally mirrors what the previous Go backend
 * issued, so existing PHP session-handling code (Session::set('roles', ...))
 * needs no changes:
 *   { user_id, tenant_id, school_id, roles: [...], academic_year_id,
 *     term_id, exp, iat }
 */
final class Jwt
{
    private static function secret(): string
    {
        $secret = Env::get('JWT_SECRET');
        if (!$secret) {
            throw new \RuntimeException('JWT_SECRET is not configured.');
        }
        return $secret;
    }

    private static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private static function base64UrlDecode(string $data): string
    {
        $remainder = strlen($data) % 4;
        if ($remainder) {
            $data .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * Issue a signed token. $claims must include at least user_id and
     * tenant_id; $ttlSeconds controls expiry (900 = 15 min for access
     * tokens, much longer for refresh tokens — caller decides which).
     */
    public static function issue(array $claims, int $ttlSeconds): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now    = time();

        $payload = array_merge($claims, [
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        $headerEncoded  = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        $signature = hash_hmac('sha256', "{$headerEncoded}.{$payloadEncoded}", self::secret(), true);
        $signatureEncoded = self::base64UrlEncode($signature);

        return "{$headerEncoded}.{$payloadEncoded}.{$signatureEncoded}";
    }

    /**
     * Verify and decode a token. Returns the claims array on success,
     * or null if the token is invalid, malformed, or expired.
     */
    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        [$headerEncoded, $payloadEncoded, $signatureEncoded] = $parts;

        $expectedSignature = hash_hmac(
            'sha256',
            "{$headerEncoded}.{$payloadEncoded}",
            self::secret(),
            true
        );
        $actualSignature = self::base64UrlDecode($signatureEncoded);

        // Constant-time comparison — avoids timing attacks on signature check.
        if (!hash_equals($expectedSignature, $actualSignature)) {
            return null;
        }

        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);
        if (!is_array($payload)) {
            return null;
        }

        if (isset($payload['exp']) && time() >= $payload['exp']) {
            return null; // expired
        }

        return $payload;
    }
}

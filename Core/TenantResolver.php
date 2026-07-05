<?php
declare(strict_types=1);

namespace Core;


final class TenantResolver
{
    private static ?array $current = null;

    public static function resolve(): ?array
    {
        if (self::$current !== null) {
            return self::$current;
        }

      
        $host = self::extractHost($_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? '');
       
         // TEMP DEBUG — remove after troubleshooting
    error_log('TenantResolver: raw HTTP_HOST=[' . ($_SERVER['HTTP_HOST'] ?? 'NULL') . '] extracted host=[' . $host . '] length=' . strlen($host));

        $pdo  = Database::connection();

        // 1. Exact domain match
        $stmt = $pdo->prepare('SELECT * FROM tenants WHERE domain = ? AND is_active = 1 LIMIT 1');
        $stmt->execute([$host]);
        $tenant = $stmt->fetch();

        if ($tenant) {
            self::$current = $tenant;
            return $tenant;
        }

        // 2. Subdomain slug match
        $slug = self::extractSlug($host);
        if ($slug !== '') {
            $stmt = $pdo->prepare('SELECT * FROM tenants WHERE domain LIKE ? AND is_active = 1 LIMIT 1');
            $stmt->execute([$slug . '%']);
            $tenant = $stmt->fetch();
        }

        self::$current = $tenant ?: null;
        return self::$current;
    }

    public static function id(): ?int
    {
        $t = self::resolve();
        return $t ? (int)$t['id'] : null;
    }

    public static function slug(): ?string
    {
        $t = self::resolve();
        return $t['slug'] ?? null;
    }

    private static function extractHost(string $hostHeader): string
    {
        $pos = strrpos($hostHeader, ':');
        if ($pos !== false && !str_contains(substr($hostHeader, $pos), ']')) {
            return substr($hostHeader, 0, $pos);
        }
        return $hostHeader;
    }

    private static function extractSlug(string $host): string
    {
        if ($host === '' || $host === 'localhost') {
            return '';
        }
        $parts = explode('.', $host);
        $n = count($parts);

        if ($parts[$n - 1] === 'localhost') {
            if ($n >= 3) return $parts[$n - 2];
            if ($n === 2) return $parts[0];
            return '';
        }

        $kenyanSlds = ['ac.ke', 'co.ke', 'or.ke', 'go.ke', 'ne.ke', 'sc.ke', 'me.ke', 'mobi.ke'];

        if ($n >= 2) {
            $sld = $parts[$n - 2] . '.' . $parts[$n - 1];
            if (in_array($sld, $kenyanSlds, true)) {
                if ($n >= 4) return $parts[$n - 3];
                if ($n === 3) return $parts[0];
            }
        }
        if ($n >= 3) return $parts[$n - 3];
        //if ($n === 2) return $parts[0];
        return '';
    }
}

<?php
declare(strict_types=1);

namespace Core;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $host    = Env::get('DB_HOST', '127.0.0.1');
            $port    = Env::get('DB_PORT', '3306');
            $name    = Env::get('DB_DATABASE');
            $user    = Env::get('DB_USERNAME');
            $pass    = Env::get('DB_PASSWORD');
            $charset = Env::get('DB_CHARSET', 'utf8mb4');

            $dsn = "mysql:host={$host};port={$port};dbname={$name};charset={$charset}";

            try {
                self::$instance = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                    PDO::ATTR_PERSISTENT         => false,
                ]);
            } catch (PDOException $e) {
    Logger::error('Database connection failed', ['error' => $e->getMessage()]);
    throw new \RuntimeException('Database connection failed: ' . $e->getMessage()); // TEMP DEBUG
}
        }
        return self::$instance;
    }

    /** Run a callback inside a transaction; rolls back on any exception. */
    public static function transaction(callable $callback): mixed
    {
        $pdo = self::connection();
        $pdo->beginTransaction();
        try {
            $result = $callback($pdo);
            $pdo->commit();
            return $result;
        } catch (\Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }
    }
}

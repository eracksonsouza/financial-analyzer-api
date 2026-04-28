<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;
use RuntimeException;

final class Connection
{
    private ?PDO $pdo = null;

    public function __construct(
        private readonly string $dsn,
        private readonly ?string $username = null,
        private readonly ?string $password = null,
    )
    {
    }

    /**
     * @param callable(string, ?string): ?string $env
     */
    public static function fromEnv(callable $env): self
    {
        $databaseUrl = $env('DATABASE_URL', null);
        if ($databaseUrl !== null) {
            return self::fromDatabaseUrl($databaseUrl);
        }

        $dsn = $env('DB_DSN', null);
        if ($dsn !== null) {
            return new self($dsn, $env('DB_USER', null), $env('DB_PASSWORD', null));
        }

        $dbPath = $env('DB_PATH', '/data/financial.db') ?? '/data/financial.db';
        return new self("sqlite:{$dbPath}");
    }

    public static function fromDatabaseUrl(string $databaseUrl): self
    {
        $parts = parse_url($databaseUrl);
        if ($parts === false) {
            throw new RuntimeException('Invalid DATABASE_URL');
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if ($scheme === 'postgres' || $scheme === 'postgresql') {
            $host = (string) ($parts['host'] ?? 'localhost');
            $port = (int) ($parts['port'] ?? 5432);
            $db   = ltrim((string) ($parts['path'] ?? ''), '/');

            $query = [];
            if (isset($parts['query'])) {
                parse_str((string) $parts['query'], $query);
            }

            $dsnParts = [
                'host'   => $host,
                'port'   => (string) $port,
                'dbname' => $db,
            ];

            if (isset($query['sslmode']) && is_string($query['sslmode']) && $query['sslmode'] !== '') {
                $dsnParts['sslmode'] = $query['sslmode'];
            }

            $dsn = 'pgsql:' . implode(';', array_map(
                static fn(string $k, string $v): string => $k . '=' . $v,
                array_keys($dsnParts),
                array_values($dsnParts)
            ));

            $user = isset($parts['user']) ? urldecode((string) $parts['user']) : null;
            $pass = isset($parts['pass']) ? urldecode((string) $parts['pass']) : null;

            return new self($dsn, $user, $pass);
        }

        if ($scheme === 'sqlite') {
            // sqlite:///absolute/path.db or sqlite:/absolute/path.db
            $path = (string) ($parts['path'] ?? '');
            if ($path === '') {
                throw new RuntimeException('Invalid sqlite DATABASE_URL');
            }

            return new self('sqlite:' . $path);
        }

        throw new RuntimeException('Unsupported DATABASE_URL scheme: ' . $scheme);
    }

    public function pdo(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = new PDO($this->dsn, $this->username, $this->password, options: [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        }

        return $this->pdo;
    }
}

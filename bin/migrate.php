<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Connection;
use PDO;
use PDOException;

/** Reads from $_ENV first, falls back to getenv() (more reliable under php -S). */
$env = static function (string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === '') ? $default : (string) $value;
};

$connection = Connection::fromEnv($env);

// Retry helps when Postgres container is still starting.
$attempts = 0;
$pdo = null;
while (true) {
    try {
        $pdo = $connection->pdo();
        break;
    } catch (PDOException $e) {
        $attempts++;
        if ($attempts >= 15) {
            throw $e;
        }
        usleep(300_000);
    }
}

if (!$pdo instanceof PDO) {
    throw new RuntimeException('Database connection not established');
}

$driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

if ($driver === 'pgsql') {
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS analyses (
            id         BIGSERIAL PRIMARY KEY,
            income     DOUBLE PRECISION NOT NULL,
            expenses   JSONB            NOT NULL,
            metrics    JSONB            NOT NULL,
            ai_result  JSONB            NOT NULL,
            created_at TIMESTAMPTZ      NOT NULL DEFAULT NOW()
        )
    SQL);

    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS transactions (
            id         BIGSERIAL PRIMARY KEY,
            title      TEXT             NOT NULL,
            amount     DOUBLE PRECISION NOT NULL,
            date       TEXT             NOT NULL,
            type       TEXT             NOT NULL,
            created_at TIMESTAMPTZ      NOT NULL DEFAULT NOW()
        )
    SQL);

    fwrite(STDOUT, "Migrations applied (PostgreSQL)\n");
    exit(0);
}

$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS analyses (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        income     REAL    NOT NULL,
        expenses   TEXT    NOT NULL,
        metrics    TEXT    NOT NULL,
        ai_result  TEXT    NOT NULL,
        created_at TEXT    NOT NULL
    )
SQL);

$pdo->exec(<<<SQL
    CREATE TABLE IF NOT EXISTS transactions (
        id         INTEGER PRIMARY KEY AUTOINCREMENT,
        title      TEXT    NOT NULL,
        amount     REAL    NOT NULL,
        date       TEXT    NOT NULL,
        type       TEXT    NOT NULL,
        created_at TEXT    NOT NULL
    )
SQL);

fwrite(STDOUT, "Migrations applied (SQLite)\n");

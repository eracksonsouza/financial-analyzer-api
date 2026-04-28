<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use App\Infrastructure\Connection;

$dbPath = $_ENV['DB_PATH'] ?? getenv('DB_PATH') ?: __DIR__ . '/../data/financial.db';

$dataDir = dirname($dbPath);
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0775, true);
}

$pdo = (new Connection($dbPath))->pdo();

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

fwrite(STDOUT, "Migrations applied at {$dbPath}\n");

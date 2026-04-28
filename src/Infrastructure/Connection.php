<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;

final class Connection
{
    private readonly PDO $pdo;

    public function __construct(string $dbPath)
    {
        $this->pdo = new PDO("sqlite:{$dbPath}", options: [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    public function pdo(): PDO
    {
        return $this->pdo;
    }
}

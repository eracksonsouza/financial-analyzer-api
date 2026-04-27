<?php

declare(strict_types=1);

namespace App\Infrastructure;

use PDO;

class Database
{
    private PDO $pdo;

    public function __construct(string $dbPath)
    {
        $this->pdo = new PDO("sqlite:{$dbPath}", options: [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        $this->migrate();
    }

    private function migrate(): void
    {
        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS analyses (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                income     REAL    NOT NULL,
                expenses   TEXT    NOT NULL,
                metrics    TEXT    NOT NULL,
                ai_result  TEXT    NOT NULL,
                created_at TEXT    NOT NULL
            )
        SQL);

        $this->pdo->exec(<<<SQL
            CREATE TABLE IF NOT EXISTS transactions (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                title      TEXT    NOT NULL,
                amount     REAL    NOT NULL,
                date       TEXT    NOT NULL,
                type       TEXT    NOT NULL,
                created_at TEXT    NOT NULL
            )
        SQL);
    }

    public function saveAnalysis(array $data): array
    {
        $stmt = $this->pdo->prepare(<<<SQL
            INSERT INTO analyses (income, expenses, metrics, ai_result, created_at)
            VALUES (:income, :expenses, :metrics, :ai_result, :created_at)
        SQL);

        $stmt->execute($data);
        $id = (int) $this->pdo->lastInsertId();

        return ['id' => $id, ...$data];
    }

    public function listAnalyses(int $limit = 20): array
    {
        return $this->pdo
            ->query("SELECT * FROM analyses ORDER BY created_at DESC LIMIT {$limit}")
            ->fetchAll();
    }

    public function findAnalysis(int $id): array|false
    {
        $stmt = $this->pdo->prepare('SELECT * FROM analyses WHERE id = ?');
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function createTransaction(array $data): array
    {
        $stmt = $this->pdo->prepare(<<<SQL
            INSERT INTO transactions (title, amount, date, type, created_at)
            VALUES (:title, :amount, :date, :type, :created_at)
        SQL);

        $stmt->execute($data);
        $id = (int) $this->pdo->lastInsertId();

        return ['id' => $id, ...$data];
    }

    public function listTransactions(?string $month = null, int $limit = 60): array
    {
        if ($month) {
            $stmt = $this->pdo->prepare(
                'SELECT * FROM transactions WHERE date LIKE :month ORDER BY date DESC LIMIT :limit'
            );
            $stmt->bindValue(':month', $month . '%');
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll();
        }

        $stmt = $this->pdo->prepare('SELECT * FROM transactions ORDER BY date DESC LIMIT :limit');
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }
}

<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\DTO\Expenses;
use App\Infrastructure\Connection;
use PDO;

final class AnalysisRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /**
     * @param array<string, mixed> $metrics
     * @param array<string, mixed> $aiResult
     * @return array<string, mixed>
     */
    public function save(float $income, Expenses $expenses, array $metrics, array $aiResult): array
    {
        $createdAt = date('Y-m-d H:i:s');

        $pdo = $this->connection->pdo();
        $driver = (string) $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'pgsql') {
            $stmt = $pdo->prepare(<<<SQL
                INSERT INTO analyses (income, expenses, metrics, ai_result, created_at)
                VALUES (:income, :expenses, :metrics, :ai_result, :created_at)
                RETURNING id
            SQL);

            $stmt->execute([
                ':income'     => $income,
                ':expenses'   => json_encode($expenses->toArray(), JSON_THROW_ON_ERROR),
                ':metrics'    => json_encode($metrics, JSON_THROW_ON_ERROR),
                ':ai_result'  => json_encode($aiResult, JSON_THROW_ON_ERROR),
                ':created_at' => $createdAt,
            ]);

            $id = (int) $stmt->fetchColumn();

            return [
                'id'         => $id,
                'income'     => $income,
                'expenses'   => $expenses->toArray(),
                'metrics'    => $metrics,
                'ai_result'  => $aiResult,
                'created_at' => $createdAt,
            ];
        }

        $stmt = $pdo->prepare(<<<SQL
            INSERT INTO analyses (income, expenses, metrics, ai_result, created_at)
            VALUES (:income, :expenses, :metrics, :ai_result, :created_at)
        SQL);

        $stmt->execute([
            ':income'     => $income,
            ':expenses'   => json_encode($expenses->toArray(), JSON_THROW_ON_ERROR),
            ':metrics'    => json_encode($metrics, JSON_THROW_ON_ERROR),
            ':ai_result'  => json_encode($aiResult, JSON_THROW_ON_ERROR),
            ':created_at' => $createdAt,
        ]);

        return [
            'id'         => (int) $pdo->lastInsertId(),
            'income'     => $income,
            'expenses'   => $expenses->toArray(),
            'metrics'    => $metrics,
            'ai_result'  => $aiResult,
            'created_at' => $createdAt,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function list(int $limit = 20): array
    {
        $stmt = $this->connection->pdo()->prepare(
            'SELECT * FROM analyses ORDER BY created_at DESC LIMIT :limit'
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }

    /** @return array<string, mixed>|null */
    public function find(int $id): ?array
    {
        $stmt = $this->connection->pdo()->prepare('SELECT * FROM analyses WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }
}

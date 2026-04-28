<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\DTO\CreateTransactionRequest;
use App\Infrastructure\Connection;
use PDO;

final class TransactionRepository
{
    public function __construct(private readonly Connection $connection)
    {
    }

    /** @return array<string, mixed> */
    public function create(CreateTransactionRequest $req): array
    {
        $createdAt = date('Y-m-d H:i:s');

        $stmt = $this->connection->pdo()->prepare(<<<SQL
            INSERT INTO transactions (title, amount, date, type, created_at)
            VALUES (:title, :amount, :date, :type, :created_at)
        SQL);

        $stmt->execute([
            ':title'      => $req->title,
            ':amount'     => $req->amount,
            ':date'       => $req->date,
            ':type'       => $req->type->value,
            ':created_at' => $createdAt,
        ]);

        return [
            'id'         => (int) $this->connection->pdo()->lastInsertId(),
            'title'      => $req->title,
            'amount'     => $req->amount,
            'date'       => $req->date,
            'type'       => $req->type->value,
            'created_at' => $createdAt,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function list(?string $month = null, int $limit = 60): array
    {
        $sql = $month !== null
            ? 'SELECT * FROM transactions WHERE date LIKE :month ORDER BY date DESC LIMIT :limit'
            : 'SELECT * FROM transactions ORDER BY date DESC LIMIT :limit';

        $stmt = $this->connection->pdo()->prepare($sql);

        if ($month !== null) {
            $stmt->bindValue(':month', $month . '%');
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll();
    }
}

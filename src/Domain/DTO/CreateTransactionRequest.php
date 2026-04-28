<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enums\TransactionType;
use App\Domain\Exceptions\ValidationException;

final class CreateTransactionRequest
{
    public function __construct(
        public readonly string $title,
        public readonly float $amount,
        public readonly string $date,
        public readonly TransactionType $type,
    ) {
    }

    /** @param array<string, mixed> $body */
    public static function fromArray(array $body): self
    {
        $title = trim((string) ($body['title'] ?? ''));
        if ($title === '') {
            throw new ValidationException('Título inválido.');
        }

        $amount = (float) ($body['amount'] ?? 0);
        if ($amount <= 0) {
            throw new ValidationException('Valor inválido.');
        }

        $date = trim((string) ($body['date'] ?? ''));
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            throw new ValidationException('Data inválida.');
        }

        $rawType = trim((string) ($body['type'] ?? ''));
        $type = TransactionType::tryFrom($rawType)
            ?? throw new ValidationException('Tipo inválido.');

        return new self($title, $amount, $date, $type);
    }
}

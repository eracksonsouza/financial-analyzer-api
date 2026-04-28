<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Exceptions\ValidationException;

final class CreateAnalysisRequest
{
    public function __construct(
        public readonly float $income,
        public readonly Expenses $expenses,
    ) {
    }

    /** @param array<string, mixed> $body */
    public static function fromArray(array $body): self
    {
        $income = (float) ($body['income'] ?? 0);
        if ($income <= 0) {
            throw new ValidationException('Renda inválida.');
        }

        $expensesRaw = $body['expenses'] ?? [];
        if (!is_array($expensesRaw)) {
            throw new ValidationException('Campo "expenses" inválido.');
        }

        return new self($income, Expenses::fromArray($expensesRaw));
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\DTO;

use App\Domain\Enums\ExpenseCategory;
use App\Domain\Enums\ExpenseGroup;
use App\Domain\Exceptions\ValidationException;

final class Expenses
{
    /** @param array<string, float> $values keyed by ExpenseCategory->value */
    private function __construct(public readonly array $values)
    {
    }

    /** @param array<string, mixed> $raw */
    public static function fromArray(array $raw): self
    {
        $values = [];
        foreach (ExpenseCategory::cases() as $cat) {
            $val = (float) ($raw[$cat->value] ?? 0);
            if ($val < 0) {
                throw new ValidationException("Despesa '{$cat->value}' não pode ser negativa.");
            }
            $values[$cat->value] = $val;
        }
        return new self($values);
    }

    public function get(ExpenseCategory $cat): float
    {
        return $this->values[$cat->value];
    }

    public function total(): float
    {
        return (float) array_sum($this->values);
    }

    public function totalByGroup(ExpenseGroup $group): float
    {
        $sum = 0.0;
        foreach (ExpenseCategory::cases() as $cat) {
            if ($cat->group() === $group) {
                $sum += $this->values[$cat->value];
            }
        }
        return $sum;
    }

    /** @return array<string, float> */
    public function toArray(): array
    {
        return $this->values;
    }
}

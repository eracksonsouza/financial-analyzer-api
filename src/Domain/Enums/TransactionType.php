<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Investment = 'investment';
}

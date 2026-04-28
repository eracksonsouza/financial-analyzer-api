<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum ExpenseGroup
{
    case Needs;
    case Wants;
    case Debt;
}

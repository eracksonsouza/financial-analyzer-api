<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\DTO\Expenses;
use App\Domain\Enums\ExpenseCategory;
use App\Domain\Enums\ExpenseGroup;

final class AnalysisService
{
    /** @return array<string, mixed> */
    public function computeMetrics(float $income, Expenses $expenses): array
    {
        $totalExpenses = $expenses->total();
        $balance       = $income - $totalExpenses;
        $savingsRate   = $income > 0 ? round(($balance / $income) * 100, 1) : 0.0;

        $expenseRatios = [];
        foreach ($expenses->toArray() as $category => $value) {
            $expenseRatios[$category] = $income > 0
                ? round(($value / $income) * 100, 1)
                : 0.0;
        }

        $needs = $expenses->totalByGroup(ExpenseGroup::Needs);
        $wants = $expenses->totalByGroup(ExpenseGroup::Wants);
        $debt  = $expenses->totalByGroup(ExpenseGroup::Debt);

        return [
            'income'         => $income,
            'total_expenses' => round($totalExpenses, 2),
            'balance'        => round($balance, 2),
            'savings_rate'   => $savingsRate,
            'expense_ratios' => $expenseRatios,
            'rule_50_30_20'  => [
                'needs_pct' => $income > 0 ? round(($needs / $income) * 100, 1) : 0.0,
                'wants_pct' => $income > 0 ? round(($wants / $income) * 100, 1) : 0.0,
                'debt_pct'  => $income > 0 ? round(($debt / $income) * 100, 1) : 0.0,
            ],
            'health_score' => $this->computeHealthScore($savingsRate, $income, $expenses),
        ];
    }

    private function computeHealthScore(float $savingsRate, float $income, Expenses $expenses): int
    {
        $score = 100;

        if ($savingsRate < 0) {
            $score -= 40;
        } elseif ($savingsRate < 10) {
            $score -= 20;
        } elseif ($savingsRate < 20) {
            $score -= 10;
        }

        $debtRatio = $income > 0
            ? ($expenses->get(ExpenseCategory::Dividas) / $income) * 100
            : 0;
        if ($debtRatio > 30) {
            $score -= 25;
        } elseif ($debtRatio > 20) {
            $score -= 15;
        } elseif ($debtRatio > 10) {
            $score -= 5;
        }

        $housingRatio = $income > 0
            ? ($expenses->get(ExpenseCategory::Moradia) / $income) * 100
            : 0;
        if ($housingRatio > 40) {
            $score -= 15;
        } elseif ($housingRatio > 30) {
            $score -= 5;
        }

        return max(0, min(100, $score));
    }
}

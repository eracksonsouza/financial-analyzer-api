<?php

declare(strict_types=1);

namespace App\Services;

class AnalysisService
{
    public static function computeMetrics(float $income, array $expenses): array
    {
        $totalExpenses = array_sum($expenses);
        $balance       = $income - $totalExpenses;
        $savingsRate   = $income > 0 ? round(($balance / $income) * 100, 1) : 0;

        $expenseRatios = [];
        foreach ($expenses as $category => $value) {
            $expenseRatios[$category] = $income > 0
                ? round(($value / $income) * 100, 1)
                : 0;
        }

        // Regra 50/30/20 — needs / wants / savings
        $needs = $expenses['moradia'] + $expenses['alimentacao']
               + $expenses['transporte'] + $expenses['saude'];
        $wants = $expenses['lazer'] + $expenses['educacao'] + $expenses['outros'];
        $debt  = $expenses['dividas'];

        return [
            'income'         => $income,
            'total_expenses' => round($totalExpenses, 2),
            'balance'        => round($balance, 2),
            'savings_rate'   => $savingsRate,
            'expense_ratios' => $expenseRatios,
            'rule_50_30_20'  => [
                'needs_pct'   => $income > 0 ? round(($needs / $income) * 100, 1) : 0,
                'wants_pct'   => $income > 0 ? round(($wants / $income) * 100, 1) : 0,
                'debt_pct'    => $income > 0 ? round(($debt / $income) * 100, 1) : 0,
            ],
            'health_score' => self::computeHealthScore($savingsRate, $income, $expenses),
        ];
    }

    private static function computeHealthScore(
        float $savingsRate,
        float $income,
        array $expenses
    ): int {
        $score = 100;

        // Penaliza taxa de poupança baixa
        if ($savingsRate < 0)   $score -= 40;
        elseif ($savingsRate < 10) $score -= 20;
        elseif ($savingsRate < 20) $score -= 10;

        // Penaliza dívidas excessivas (>30% da renda)
        $debtRatio = $income > 0 ? ($expenses['dividas'] / $income) * 100 : 0;
        if ($debtRatio > 30) $score -= 25;
        elseif ($debtRatio > 20) $score -= 15;
        elseif ($debtRatio > 10) $score -= 5;

        // Penaliza moradia excessiva (>40% da renda)
        $housingRatio = $income > 0 ? ($expenses['moradia'] / $income) * 100 : 0;
        if ($housingRatio > 40) $score -= 15;
        elseif ($housingRatio > 30) $score -= 5;

        return max(0, min(100, $score));
    }
}

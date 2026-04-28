<?php

declare(strict_types=1);

namespace App\Tests\Unit\Services;

use App\Domain\DTO\Expenses;
use App\Services\AnalysisService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AnalysisService::class)]
final class AnalysisServiceTest extends TestCase
{
    private AnalysisService $service;

    protected function setUp(): void
    {
        $this->service = new AnalysisService();
    }

    public function testComputeMetricsReturnsExpectedRatios(): void
    {
        $expenses = Expenses::fromArray([
            'moradia'     => 1500,
            'alimentacao' => 800,
            'transporte'  => 300,
            'saude'       => 200,
            'lazer'       => 250,
            'educacao'    => 0,
            'dividas'     => 500,
            'outros'      => 100,
        ]);

        $metrics = $this->service->computeMetrics(5000.0, $expenses);

        self::assertSame(3650.0, $metrics['total_expenses']);
        self::assertSame(1350.0, $metrics['balance']);
        self::assertSame(27.0, $metrics['savings_rate']);
        self::assertSame(56.0, $metrics['rule_50_30_20']['needs_pct']);
        self::assertSame(7.0, $metrics['rule_50_30_20']['wants_pct']);
        self::assertSame(10.0, $metrics['rule_50_30_20']['debt_pct']);
    }

    public function testHealthScoreIsMaxWhenSavingsHighAndNoDebt(): void
    {
        $expenses = Expenses::fromArray([
            'moradia'     => 1000,
            'alimentacao' => 500,
            'transporte'  => 200,
            'saude'       => 100,
            'lazer'       => 100,
            'educacao'    => 100,
            'dividas'     => 0,
            'outros'      => 0,
        ]);

        $metrics = $this->service->computeMetrics(10000.0, $expenses);

        self::assertSame(100, $metrics['health_score']);
    }

    public function testHealthScorePenalizesNegativeSavingsAndOverHousing(): void
    {
        $expenses = Expenses::fromArray([
            'moradia'     => 3000,
            'alimentacao' => 2000,
            'transporte'  => 1000,
            'saude'       => 500,
            'lazer'       => 0,
            'educacao'    => 0,
            'dividas'     => 0,
            'outros'      => 0,
        ]);

        $metrics = $this->service->computeMetrics(5000.0, $expenses);

        self::assertLessThan(0, $metrics['savings_rate']);
        self::assertSame(45, $metrics['health_score']);
    }

    public function testZeroIncomeDoesNotDivideByZero(): void
    {
        $expenses = Expenses::fromArray([]);

        $metrics = $this->service->computeMetrics(0.0, $expenses);

        self::assertSame(0.0, $metrics['savings_rate']);
        self::assertSame(0.0, $metrics['rule_50_30_20']['needs_pct']);
        self::assertSame(0.0, $metrics['rule_50_30_20']['wants_pct']);
        self::assertSame(0.0, $metrics['rule_50_30_20']['debt_pct']);
    }
}

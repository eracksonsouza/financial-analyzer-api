<?php

declare(strict_types=1);

namespace App\Domain\Enums;

enum ExpenseCategory: string
{
    case Moradia = 'moradia';
    case Alimentacao = 'alimentacao';
    case Transporte = 'transporte';
    case Saude = 'saude';
    case Lazer = 'lazer';
    case Educacao = 'educacao';
    case Dividas = 'dividas';
    case Outros = 'outros';

    public function label(): string
    {
        return match ($this) {
            self::Moradia     => 'Moradia',
            self::Alimentacao => 'Alimentação',
            self::Transporte  => 'Transporte',
            self::Saude       => 'Saúde',
            self::Lazer       => 'Lazer',
            self::Educacao    => 'Educação',
            self::Dividas     => 'Dívidas',
            self::Outros      => 'Outros',
        };
    }

    public function group(): ExpenseGroup
    {
        return match ($this) {
            self::Moradia, self::Alimentacao, self::Transporte, self::Saude => ExpenseGroup::Needs,
            self::Lazer, self::Educacao, self::Outros                        => ExpenseGroup::Wants,
            self::Dividas                                                    => ExpenseGroup::Debt,
        };
    }
}

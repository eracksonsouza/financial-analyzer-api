<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain;

use App\Domain\DTO\CreateTransactionRequest;
use App\Domain\Enums\TransactionType;
use App\Domain\Exceptions\ValidationException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CreateTransactionRequest::class)]
final class CreateTransactionRequestTest extends TestCase
{
    public function testValidPayloadParsesEnum(): void
    {
        $dto = CreateTransactionRequest::fromArray([
            'title'  => 'Salário',
            'amount' => 5000,
            'date'   => '2026-04-27',
            'type'   => 'income',
        ]);

        self::assertSame('Salário', $dto->title);
        self::assertSame(5000.0, $dto->amount);
        self::assertSame('2026-04-27', $dto->date);
        self::assertSame(TransactionType::Income, $dto->type);
    }

    public function testInvalidTypeThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Tipo inválido.');

        CreateTransactionRequest::fromArray([
            'title'  => 'X',
            'amount' => 10,
            'date'   => '2026-04-27',
            'type'   => 'unknown',
        ]);
    }

    public function testEmptyTitleThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Título inválido.');

        CreateTransactionRequest::fromArray([
            'title'  => '   ',
            'amount' => 10,
            'date'   => '2026-04-27',
            'type'   => 'income',
        ]);
    }

    public function testNonPositiveAmountThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Valor inválido.');

        CreateTransactionRequest::fromArray([
            'title'  => 'X',
            'amount' => 0,
            'date'   => '2026-04-27',
            'type'   => 'income',
        ]);
    }

    public function testMalformedDateThrows(): void
    {
        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Data inválida.');

        CreateTransactionRequest::fromArray([
            'title'  => 'X',
            'amount' => 10,
            'date'   => '27/04/2026',
            'type'   => 'income',
        ]);
    }
}

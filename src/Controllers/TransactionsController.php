<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\DTO\CreateTransactionRequest;
use App\Domain\Exceptions\ValidationException;
use App\Repositories\TransactionRepository;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

final class TransactionsController
{
    public function __construct(private readonly TransactionRepository $repo)
    {
    }

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $month  = isset($params['month']) ? trim((string) $params['month']) : null;

        if ($month !== null && $month !== '' && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->json($response, ['error' => 'Mês inválido.'], 422);
        }

        $records = $this->repo->list($month ?: null);

        return $this->json($response, array_map(fn(array $r) => [
            'id'         => (int) $r['id'],
            'title'      => $r['title'],
            'amount'     => (float) $r['amount'],
            'date'       => $r['date'],
            'type'       => $r['type'],
            'created_at' => $r['created_at'],
        ], $records));
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $dto = CreateTransactionRequest::fromArray((array) $request->getParsedBody());
        } catch (ValidationException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 422);
        }

        $record = $this->repo->create($dto);

        return $this->json($response, [
            'id'         => (int) $record['id'],
            'title'      => $record['title'],
            'amount'     => (float) $record['amount'],
            'date'       => $record['date'],
            'type'       => $record['type'],
            'created_at' => $record['created_at'],
        ], 201);
    }

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

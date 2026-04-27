<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Infrastructure\Database;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class TransactionsController
{
    public function __construct(private readonly Database $db) {}

    public function index(Request $request, Response $response): Response
    {
        $params = $request->getQueryParams();
        $month = isset($params['month']) ? trim((string) $params['month']) : null;

        if ($month !== null && $month !== '' && !preg_match('/^\d{4}-\d{2}$/', $month)) {
            return $this->json($response, ['error' => 'Mês inválido.'], 422);
        }

        $records = $this->db->listTransactions($month ?: null);

        return $this->json($response, array_map(fn($record) => [
            'id' => $record['id'],
            'title' => $record['title'],
            'amount' => (float) $record['amount'],
            'date' => $record['date'],
            'type' => $record['type'],
            'created_at' => $record['created_at'],
        ], $records));
    }

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $title = trim((string) ($body['title'] ?? ''));
        $amount = (float) ($body['amount'] ?? 0);
        $date = trim((string) ($body['date'] ?? ''));
        $type = trim((string) ($body['type'] ?? ''));

        if ($title === '') {
            return $this->json($response, ['error' => 'Título inválido.'], 422);
        }

        if ($amount <= 0) {
            return $this->json($response, ['error' => 'Valor inválido.'], 422);
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json($response, ['error' => 'Data inválida.'], 422);
        }

        $allowed = ['income', 'expense', 'investment'];
        if (!in_array($type, $allowed, true)) {
            return $this->json($response, ['error' => 'Tipo inválido.'], 422);
        }

        $record = $this->db->createTransaction([
            'title' => $title,
            'amount' => $amount,
            'date' => $date,
            'type' => $type,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->json($response, [
            'id' => $record['id'],
            'title' => $record['title'],
            'amount' => (float) $record['amount'],
            'date' => $record['date'],
            'type' => $record['type'],
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

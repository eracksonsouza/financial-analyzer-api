<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Infrastructure\Database;
use App\Services\AnalysisService;
use App\Services\ClaudeService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class AnalysisController
{
    public function __construct(
        private readonly Database $db,
        private readonly ClaudeService $claude
    ) {}

    public function create(Request $request, Response $response): Response
    {
        $body = $request->getParsedBody();

        $income = (float) ($body['income'] ?? 0);
        if ($income <= 0) {
            return $this->json($response, ['error' => 'Renda inválida.'], 422);
        }

        $expenses = [
            'moradia'     => (float) ($body['expenses']['moradia'] ?? 0),
            'alimentacao' => (float) ($body['expenses']['alimentacao'] ?? 0),
            'transporte'  => (float) ($body['expenses']['transporte'] ?? 0),
            'saude'       => (float) ($body['expenses']['saude'] ?? 0),
            'lazer'       => (float) ($body['expenses']['lazer'] ?? 0),
            'educacao'    => (float) ($body['expenses']['educacao'] ?? 0),
            'dividas'     => (float) ($body['expenses']['dividas'] ?? 0),
            'outros'      => (float) ($body['expenses']['outros'] ?? 0),
        ];

        $metrics = AnalysisService::computeMetrics($income, $expenses);
        $aiResult = $this->claude->analyze($income, $expenses, $metrics);

        $record = $this->db->saveAnalysis([
            'income'      => $income,
            'expenses'    => json_encode($expenses),
            'metrics'     => json_encode($metrics),
            'ai_result'   => json_encode($aiResult),
            'created_at'  => date('Y-m-d H:i:s'),
        ]);

        return $this->json($response, [
            'id'      => $record['id'],
            'metrics' => $metrics,
            'ai'      => $aiResult,
        ], 201);
    }

    public function history(Request $request, Response $response): Response
    {
        $records = $this->db->listAnalyses();

        $result = array_map(fn($r) => [
            'id'         => $r['id'],
            'income'     => $r['income'],
            'metrics'    => json_decode($r['metrics'], true),
            'created_at' => $r['created_at'],
        ], $records);

        return $this->json($response, $result);
    }

    public function show(Request $request, Response $response, array $args): Response
    {
        $record = $this->db->findAnalysis((int) $args['id']);

        if (!$record) {
            return $this->json($response, ['error' => 'Não encontrado.'], 404);
        }

        return $this->json($response, [
            'id'         => $record['id'],
            'income'     => $record['income'],
            'expenses'   => json_decode($record['expenses'], true),
            'metrics'    => json_decode($record['metrics'], true),
            'ai'         => json_decode($record['ai_result'], true),
            'created_at' => $record['created_at'],
        ]);
    }

    private function json(Response $response, mixed $data, int $status = 200): Response
    {
        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus($status);
    }
}

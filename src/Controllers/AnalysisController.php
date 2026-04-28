<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Domain\DTO\CreateAnalysisRequest;
use App\Domain\Exceptions\AIServiceException;
use App\Domain\Exceptions\ValidationException;
use App\Repositories\AnalysisRepository;
use App\Services\AIService;
use App\Services\AnalysisService;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Log\LoggerInterface;

final class AnalysisController
{
    public function __construct(
        private readonly AnalysisRepository $repo,
        private readonly AnalysisService $analysis,
        private readonly AIService $ai,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function create(Request $request, Response $response): Response
    {
        try {
            $dto = CreateAnalysisRequest::fromArray((array) $request->getParsedBody());
        } catch (ValidationException $e) {
            return $this->json($response, ['error' => $e->getMessage()], 422);
        }

        $metrics = $this->analysis->computeMetrics($dto->income, $dto->expenses);

        try {
            $aiResult = $this->ai->analyze($dto->income, $dto->expenses, $metrics);
        } catch (AIServiceException $e) {
            $this->logger->warning('Análise abortada por falha na IA', ['error' => $e->getMessage()]);
            return $this->json($response, ['error' => 'Serviço de IA indisponível no momento.'], 502);
        }

        $record = $this->repo->save($dto->income, $dto->expenses, $metrics, $aiResult);

        return $this->json($response, [
            'id'      => $record['id'],
            'metrics' => $metrics,
            'ai'      => $aiResult,
        ], 201);
    }

    public function history(Request $request, Response $response): Response
    {
        $records = $this->repo->list();

        $result = array_map(fn(array $r) => [
            'id'         => (int) $r['id'],
            'income'     => (float) $r['income'],
            'metrics'    => json_decode($r['metrics'], true),
            'created_at' => $r['created_at'],
        ], $records);

        return $this->json($response, $result);
    }

    /** @param array<string, string> $args */
    public function show(Request $request, Response $response, array $args): Response
    {
        $record = $this->repo->find((int) $args['id']);

        if ($record === null) {
            return $this->json($response, ['error' => 'Não encontrado.'], 404);
        }

        return $this->json($response, [
            'id'         => (int) $record['id'],
            'income'     => (float) $record['income'],
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

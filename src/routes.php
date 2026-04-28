<?php

declare(strict_types=1);

use App\Controllers\AnalysisController;
use App\Controllers\TransactionsController;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\App;

return function (App $app): void {
    $app->get('/health', function (Request $request, Response $response): Response {
        $response->getBody()->write((string) json_encode(['status' => 'ok']));
        return $response->withHeader('Content-Type', 'application/json');
    });

    $app->post('/api/analysis', [AnalysisController::class, 'create']);
    $app->get('/api/analysis/history', [AnalysisController::class, 'history']);
    $app->get('/api/analysis/{id}', [AnalysisController::class, 'show']);

    $app->get('/api/transactions', [TransactionsController::class, 'index']);
    $app->post('/api/transactions', [TransactionsController::class, 'create']);
};

<?php

declare(strict_types=1);

use App\Controllers\AnalysisController;
use App\Controllers\TransactionsController;

$app->get('/health', function ($request, $response) {
    $response->getBody()->write(json_encode(['status' => 'ok']));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->post('/api/analysis', [AnalysisController::class, 'create']);
$app->get('/api/analysis/history', [AnalysisController::class, 'history']);
$app->get('/api/analysis/{id}', [AnalysisController::class, 'show']);

$app->get('/api/transactions', [TransactionsController::class, 'index']);
$app->post('/api/transactions', [TransactionsController::class, 'create']);

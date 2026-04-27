<?php

declare(strict_types=1);

use App\Infrastructure\Database;
use App\Services\ClaudeService;

return [
    Database::class => function () {
        return new Database($_ENV['DB_PATH'] ?? '/data/financial.db');
    },

    ClaudeService::class => function () {
        $apiKey = $_ENV['ANTHROPIC_API_KEY'] ?? throw new RuntimeException('ANTHROPIC_API_KEY not set');
        return new ClaudeService($apiKey);
    },
];

<?php

declare(strict_types=1);

use App\Infrastructure\Connection;
use App\Services\AIService;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

/** Reads from $_ENV first, falls back to getenv() (more reliable under php -S). */
$env = static function (string $key, ?string $default = null): ?string {
    $value = $_ENV[$key] ?? getenv($key);
    return ($value === false || $value === '') ? $default : (string) $value;
};

return [
    Connection::class => static function () use ($env) {
        return new Connection($env('DB_PATH', '/data/financial.db') ?? '/data/financial.db');
    },

    LoggerInterface::class => static function () use ($env) {
        $debug = filter_var($env('APP_DEBUG', 'false'), FILTER_VALIDATE_BOOL);
        $level = $debug ? Level::Debug : Level::Info;

        $logger = new Logger('app');
        $logger->pushHandler(new StreamHandler('php://stderr', $level));

        return $logger;
    },

    AIService::class => static function (\Psr\Container\ContainerInterface $c) use ($env) {
        $apiKey = $env('AI_API_KEY') ?? throw new RuntimeException('AI_API_KEY not set');
        $apiUrl = $env('AI_API_URL', 'https://api.openai.com/v1/chat/completions');
        $model  = $env('AI_MODEL', 'gpt-4o-mini');

        return new AIService(
            $apiKey,
            $apiUrl ?? 'https://api.openai.com/v1/chat/completions',
            $model ?? 'gpt-4o-mini',
            $c->get(LoggerInterface::class),
        );
    },
];

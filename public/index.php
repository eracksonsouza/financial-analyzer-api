<?php

declare(strict_types=1);

use DI\ContainerBuilder;
use Psr\Log\LoggerInterface;
use Slim\Factory\AppFactory;

require __DIR__ . '/../vendor/autoload.php';

$debug = filter_var($_ENV['APP_DEBUG'] ?? getenv('APP_DEBUG') ?: false, FILTER_VALIDATE_BOOL);

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions(__DIR__ . '/../src/dependencies.php');
$container = $containerBuilder->build();

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware($debug, true, true);
$errorMiddleware->setDefaultErrorHandler(
    new \Slim\Handlers\ErrorHandler(
        $app->getCallableResolver(),
        $app->getResponseFactory(),
        $container->get(LoggerInterface::class),
    )
);

// CORS
$app->add(function ($request, $handler) {
    $allowedEnv = $_ENV['CORS_ALLOWED_ORIGINS'] ?? getenv('CORS_ALLOWED_ORIGINS') ?: 'http://localhost:5173';
    $origins = array_values(array_filter(array_map('trim', explode(',', (string) $allowedEnv))));
    if ($origins === []) {
        $origins = ['http://localhost:5173'];
    }

    $origin = $request->getHeaderLine('Origin');
    $allowed = in_array($origin, $origins, true) ? $origin : $origins[0];

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowed)
        ->withHeader('Vary', 'Origin')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
});

$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

(require __DIR__ . '/../src/routes.php')($app);

$app->run();

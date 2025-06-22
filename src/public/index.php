<?php
use Slim\Factory\AppFactory;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response;

date_default_timezone_set('America/Argentina/Buenos_Aires'); // Setea la zona horaria

require __DIR__ . '/../../vendor/autoload.php';

define('MAZO_SERVIDOR', 1);

$app = AppFactory::create();

// Middleware CORS para permitir solicitudes desde frontend (ajustar dominio)
$app->add(function (Request $request, RequestHandler $handler) {
    // Si es preflight OPTIONS, devolver 200 con headers CORS
    if (strtoupper($request->getMethod()) === 'OPTIONS') {
        $response = new Response();
        return $response
            ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
            ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
            ->withStatus(200);
    }

    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', 'http://localhost:5173')
        ->withHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

$app->addBodyParsingMiddleware();

$routes = require __DIR__ . '/routes.php';
$routes($app);

$app->run();

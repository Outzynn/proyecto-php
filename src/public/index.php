<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

date_default_timezone_set('America/Argentina/Buenos_Aires'); // Setea la zona horaria

require __DIR__ . '/../../vendor/autoload.php';

$app = AppFactory::create();

// Middleware para parsear cuerpos JSON
$app->addBodyParsingMiddleware();

// Importar y registrar rutas
$routes = require __DIR__ . '/routes.php';
$routes($app);

$app->run();

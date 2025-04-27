<?php
use Slim\Factory\AppFactory;

date_default_timezone_set('America/Argentina/Buenos_Aires'); // Setea la zona horaria

require __DIR__ . '/../../vendor/autoload.php';

define('MAZO_SERVIDOR', 1);

$app = AppFactory::create();

$app->addBodyParsingMiddleware();

$routes = require __DIR__ . '/routes.php';
$routes($app);

$app->run();

<?php
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;

use App\Controllers\HomeController;
use App\Controllers\RegisterController;
use App\Controllers\LoginController;
use App\Controllers\MazosController;
use App\Controllers\UsuarioController;
use App\Controllers\PartidaController;
use App\Controllers\CartaController;
use App\Controllers\EstadisticasController;

use App\Middleware\AuthMiddleware;

date_default_timezone_set('America/Argentina/Buenos_Aires'); //Setea la zona horaria, para solucionar problemas de incompatibilidad.

require __DIR__ . '/../../vendor/autoload.php';

$app = AppFactory::create();
//$app->setBasePath('/proyecto-php/src/public'); Esto es para que funcione en XAMPP en mi PC(agus).
$app->addBodyParsingMiddleware();

$app->get('/', HomeController::Class . ':index'); //Esto es solamente de prueba, para tener un index.
 
$app->post('/registro', [new RegisterController(), 'registrarUsuario']);

$app->post('/login', [new LoginController(), 'logearUsuario']);

$app->group('/usuarios/{usuario}', function ($route) {              //Esto basicamente es una "carpeta" para juntar las rutas de /usuarios/{usuario}, ya que hay 2 para implementar (put y get)
    $route->put('', [UsuarioController::class, 'editarUsuario']);
    $route->get('', [UsuarioController::class, 'obtenerUsuario']);
})->add(new AuthMiddleware());                                      //Le agrega el autentificador de token para saber si el usuario que hace la peticion tiene o no permisos para hacerlo.

$app->post('/partidas', [new PartidaController(), 'crearPartida'])->add(new AuthMiddleware());

$app->post('/mazos', [new MazosController(), 'crearMazo'])->add(new AuthMiddleware());

$app->delete('/mazos/{mazo}', [new MazosController(), 'borrarMazo'])->add(new AuthMiddleware()); //crear GROUP  
//$app->put('/mazos/{mazo}', [new MazosController(), 'actualizarMazo'])->add(new AuthMiddleware());

$app->get('/usuarios/{usuario}/mazos', [new MazosController(),'listadoMazos'])->add(new AuthMiddleware());

$app->get('/cartas?atributo={atributo}&nombre={nombre}', [new CartaController(), 'listarCartas']); //TESTEAR

//$app->get('/estadisticas', [new EstadisticasController(), 'cantidadPartidas']); //IMPLEMENTAR

$app->run();
?>

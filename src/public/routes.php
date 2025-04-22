<?php

use App\Controllers\HomeController;
use App\Controllers\RegisterController;
use App\Controllers\LoginController;
use App\Controllers\MazosController;
use App\Controllers\UsuarioController;
use App\Controllers\PartidaController;
use App\Controllers\CartaController;
use App\Controllers\EstadisticasController;
use App\Middleware\AuthMiddleware;

return function ($app) {
    $app->get('/', HomeController::Class . ':index'); // Esto es solamente de prueba, para tener un index.

    $app->post('/registro', [new RegisterController(), 'registrarUsuario']);

    $app->post('/login', [new LoginController(), 'logearUsuario']);

    $app->group('/usuarios/{usuario}', function ($route) { 
        $route->put('', [UsuarioController::class, 'editarUsuario']);
        $route->get('', [UsuarioController::class, 'obtenerUsuario']);
    })->add(new AuthMiddleware()); 

    $app->post('/partidas', [new PartidaController(), 'crearPartida'])->add(new AuthMiddleware());

    $app->post('/mazos', [new MazosController(), 'crearMazo'])->add(new AuthMiddleware());

    $app->group('/mazos/{mazo}', function ($route) {
        $route->delete('', [new MazosController(), 'borrarMazo']);
        $route->put('', [new MazosController(), 'actualizarMazo']);
    })->add(new AuthMiddleware());

    $app->get('/usuarios/{usuario}/mazos', [new MazosController(), 'listadoMazos'])->add(new AuthMiddleware());

    $app->get('/cartas', [new CartaController(), 'listarCartas']);

    $app->get('/estadisticas', [new PartidaController(),'obtenerEstadisticas']);

};

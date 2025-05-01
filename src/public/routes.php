<?php

use App\Controllers\RegisterController;
use App\Controllers\LoginController;
use App\Controllers\MazosController;
use App\Controllers\UsuarioController;
use App\Controllers\PartidaController;
use App\Controllers\CartaController;
use App\Controllers\JugadaController;

use App\Middleware\AuthMiddleware;

return function ($app) {
    $app->post('/registro', [new RegisterController(), 'registrarUsuario']);

    $app->post('/login', [new LoginController(), 'logearUsuario']);

    $app->group('/usuarios/{usuario}', function ($route) { 
        $route->put('', [new UsuarioController(), 'editarUsuario']);
        $route->get('', [new UsuarioController(), 'obtenerUsuario']);
        $route->get('/partida/{partida}/cartas', [new JugadaController(), 'obtenerCartasEnMano']);
        $route->get('/mazos', [new MazosController(), 'listadoMazos']);
    })->add(new AuthMiddleware()); 

    $app->post('/partidas', [new PartidaController(), 'crearPartida'])->add(new AuthMiddleware());

    $app->post('/mazos', [new MazosController(), 'crearMazo'])->add(new AuthMiddleware());

    $app->group('/mazos/{mazo}', function ($route) {
        $route->delete('', [new MazosController(), 'borrarMazo']);
        $route->put('', [new MazosController(), 'actualizarMazo']);
    })->add(new AuthMiddleware());

    $app->get('/cartas', [new CartaController(), 'listarCartas']);

    $app->get('/estadisticas', [new PartidaController(),'obtenerEstadisticas']);

    $app->post('/jugadas', [new JugadaController(),'jugar'])->add(new AuthMiddleware());
};

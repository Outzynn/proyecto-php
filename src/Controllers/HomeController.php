<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class HomeController{
    function index($req,$res,$args){
        $res->getBody()->write('Bienvenido al home desde la clase controller');
        return $res;
    }
}
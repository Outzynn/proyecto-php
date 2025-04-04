<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\ResponseUtil;
use App\Utils\FunctionServer;
use App\Utils\DataBase;

class HomeController{

    private $pdo;

    function index($req,$res,$args){
        
        $this->pdo = DataBase::getInstance();

        $id = FunctionServer::jugadaServidor($this->pdo);

        if ($id === null) {
            return ResponseUtil::crearRespuesta($res, ["error: " => "No hay cartas disponibles"],400);
        }

        return ResponseUtil::crearRespuesta($res, ["carta: " => $id],200);
    }
}
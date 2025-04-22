<?php
namespace App\Controllers;

use ResponseUtil;
use DataBase;

class EstadisticasController{
    private $pdo;

    public function __construct(){
        $this->pdo = DataBase::getInstance();
    }

    public function cantidadPartidas($req,$res){
        //DEVUELVE LA CANTIDAD DE PARTIDAS GANADAS, EMPATADAS Y PERDIDAS DE TODOS LOS USUARIOS.
    }
}
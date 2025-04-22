<?php
namespace App\Controllers;

use App\Controllers\ResponseUtil;
use App\Controllers\DataBase;

class CartaController{
    private $pdo;

    public function __construct(){
        $this->pdo = DataBase::getInstance();
    }

    public function listarCartas($req,$res,$args){
        $atributo_id = $args['atributo'];
        $nombre = $args['nombre'];

        try{
            $sql = "SELECT id,nombre,ataque,ataque_nombre FROM carta WHERE atributo_id = :atributo_id AND nombre = LIKE %:nombre%"; //verificar si era asi.
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'atributo_id' => $atributo_id,
                'nombre' => $nombre
            ]);
            $cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if($cartas){
                return ResponseUtil::crearRespuesta($res,['mensaje' => 'No existen cartas con esos argumentos.']);
            }
            return ResponseUtil::crearRespuesta($res,$cartas);

        }catch(\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al procesar la solicitud: " . $e->getMessage()], 500);

        }
    }
}
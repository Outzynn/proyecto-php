<?php
namespace App\Controllers;

use PDO;
use Firebase\JWT\JWT;
use App\Utils\ResponseUtil;
use App\Utils\DataBase;

class PartidaController{
    private $pdo;

    public function __construct(){
        $this->pdo = DataBase::getInstance();
    }

    private function pertenece(int $usuarioID, int $idMazo){
        $sql = "SELECT usuario_id FROM mazo WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $idMazo]);
        $idEncontrado = $stmt->fetchColumn();

        return (int)$idEncontrado === $usuarioID;

    }

    public function crearPartida($req,$res){

        $data = $req->getParsedBody();
        $idMazo = $data['idDelMazo'];
        $usuario = $req->getAttribute("usuarioId");
        echo($usuario);

        if (!$this->pertenece($usuario,$idMazo)){
            return ResponseUtil::crearRespuesta($res,["error" => "El mazo no pertenece al usuario logeado."], 401);
        }

        $sql = "INSERT INTO partida (usuario_id, fecha, estado) VALUES (:usuarioId, NOW(), :estado)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'usuarioId' => $usuario,
            'estado' => "en_curso"
        ]);

        $partidaId = $this->pdo->lastInserId();

        $sql = "UPDATE mazo_carta SET estado = 'en_mano' WHERE mazo_id = :idMazo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idMazo' => $idMazo]);

        $sql = "SELECT carta_id FROM mazo_carta WHERE mazo_id = :idMazo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['idMazo' => $idMazo]);
        $cartas = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return ResponseUtil::crearRespuesta($res, [
            "partida_id:" => $partidaId,
            "cartas" => $cartas
        ],200);
    }
}
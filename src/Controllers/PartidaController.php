<?php
namespace App\Controllers;

use PDO;
use App\Utils\ResponseUtil;
use App\Utils\DataBase;

class PartidaController {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getInstance();
    }

    private function pertenece(int $usuarioId, int $idMazo) {
        $sql = "SELECT usuario_id FROM mazo WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['id' => $idMazo]);
        $idEncontrado = $stmt->fetchColumn();

        return (int)$idEncontrado === $usuarioId;
    }

    public function crearPartida($req, $res) {
        $data = $req->getParsedBody();
        $idMazo = $data['idDelMazo'];
        $usuarioId = $req->getAttribute("usuarioId");

        if (!$this->pertenece($usuarioId, $idMazo)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "El mazo no pertenece al usuario logeado."], 401);
        }

        try {
            $sql = "INSERT INTO partida (usuario_id, fecha, mazo_id, estado) VALUES (:usuarioId, NOW(), :idDelMazo, :estado)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'usuarioId' => $usuarioId,
                'idDelMazo' => $idMazo,
                'estado' => "en_curso"
            ]);

            $partidaId = $this->pdo->lastInsertId();

            $sql = "UPDATE mazo_carta SET estado = 'en_mano' WHERE mazo_id = :idMazo";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['idMazo' => $idMazo]);

            $sql = "SELECT carta_id FROM mazo_carta WHERE mazo_id = :idMazo";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(['idMazo' => $idMazo]);
            $cartas = $stmt->fetchAll(PDO::FETCH_COLUMN);

            return ResponseUtil::crearRespuesta($res, [
                "partida_id" => $partidaId,
                "cartas" => $cartas
            ], 200);

        } catch (PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error en la base de datos: " . $e->getMessage()], 500);
        }
    }
}
?>
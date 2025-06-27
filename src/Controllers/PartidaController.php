<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Utils\ResponseUtil;
use App\Models\PartidaModel;

class PartidaController {
    private $partidaModel;

    public function __construct() {
        $this->partidaModel = new PartidaModel();
    }

    public function crearPartida(Request $req, Response $res) {
        $data = $req->getParsedBody();
        $idMazo = $data['idDelMazo'] ?? null;
        $usuarioId = $req->getAttribute("usuarioId");

        if (!$idMazo) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Falta el ID del mazo"], 400);
        }

        try {
            if($this->partidaModel->partidaEnCurso()){
                return ResponseUtil::crearRespuesta($res,["error" => "Partida en curso del servidor, no se puede crear."],400);
            }

            if (!$this->partidaModel->mazoPerteneceAlUsuario($idMazo, $usuarioId)) {
                return ResponseUtil::crearRespuesta($res, ["error" => "El mazo no pertenece al usuario logeado."], 401);
            }

            if($this->partidaModel->mazoEnUso($idMazo)){
                return ResponseUtil::crearRespuesta($res, ["error" => "El mazo ya esta en uso."], 400);
            }

            $partidaId = $this->partidaModel->crearPartida($usuarioId, $idMazo);
            $this->partidaModel->ponerCartasEnMano($idMazo); //pone las cartas del jugador en mano
            $this->partidaModel->ponerCartasEnMano(MAZO_SERVIDOR); //pone las cartas del servidor en mano
            $cartas = $this->partidaModel->obtenerCartasDelMazo($idMazo);

            return ResponseUtil::crearRespuesta($res, [
                "partida_id" => $partidaId,
                "cartas" => $cartas
            ], 200);
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error al crear la partida: " . $e->getMessage()], 500);
        }
    }

    public function obtenerEstadisticas(Request $req, Response $res, Array $args) 
    {
        try {
            $estadisticas = $this->partidaModel->obtenerEstadisticas();

            if (!$estadisticas) {
                return ResponseUtil::crearRespuesta($res, ["error" => "No se pudieron obtener las estadísticas"], 400);
            }

            // Responder con las estadísticas
            return ResponseUtil::crearRespuesta($res, [
                "estadisticas" => $estadisticas
            ]);
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al obtener estadísticas: " . $e->getMessage()], 500);
        }
    }

}

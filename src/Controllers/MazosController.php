<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Models\MazoModel;
use App\Utils\ResponseUtil;


class MazosController{
    private $mazoModel;

    public function __construct(){
        $this->mazoModel = new MazoModel();
    }

    public function crearMazo(Request $req, Response $res)
    {
        $data = $req->getParsedBody();
        $ids = $data['ids'];
        $nombre = $data['nombre'];
        $usuario = $req->getAttribute('usuarioId');

        $error = $this->validarDatos($ids, $nombre, $usuario);

        if ($error) {
            return ResponseUtil::crearRespuesta($res, ['error' => $error], 400);
        }

        try {
            $mazoId = $this->mazoModel->crearMazo($usuario, $nombre);
            $this->mazoModel->insertarCartasEnMazo($mazoId, $ids);

            return ResponseUtil::crearRespuesta($res, ['mensaje' => "Mazo creado con ID $mazoId, nombre: $nombre"],201);
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => $e->getMessage()], 500);
        }
    }

    private function validarDatos($ids, $nombre, $usuario)
    {
        if (!$ids || !is_array($ids)) {
            return 'Se debe proporcionar un array de IDs de cartas. Llamado ids.';
        }

        if (empty($nombre)) {
            return 'El nombre del mazo es obligatorio.';
        }

        if (!$this->mazoModel->cartasValidas($ids)) {
            return 'Máximo 5 cartas distintas y todas deben existir';
        }

        if ($this->mazoModel->contarMazosDeUsuario($usuario) >= 3) {
            return 'Máximo de mazos alcanzado';
        }

        return null;
    }


    public function borrarMazo(Request $req, Response $res, Array $args)
    {
        $usuarioID = $req->getAttribute('usuarioId');
        $idDelMazo = $args['mazo'];

        if (!$idDelMazo) {
            return ResponseUtil::crearRespuesta($res, ["error" => "El mazo no debe ser nulo."], 400);
        }

        if (!$this->mazoModel->mazoPerteneceAUsuario($idDelMazo, $usuarioID)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "El mazo no existe o no pertenece al usuario."], 403);
        }

        if ($this->mazoModel->mazoEnUso($idDelMazo)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "El mazo está en uso y no puede eliminarse."], 400);
        }

        try {
            $eliminado = $this->mazoModel->eliminarMazo($idDelMazo);

            if ($eliminado) {
                return ResponseUtil::crearRespuesta($res, ["mensaje" => "Mazo eliminado con éxito."]);
            } else {
                return ResponseUtil::crearRespuesta($res, ["error" => "No se pudo eliminar el mazo."], 400);
            }
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al eliminar: " . $e->getMessage()], 500);
        }
    }

    public function listadoMazos(Request $req, Response $res, Array $args)
    {
        $usuario = $args['usuario'];
        $usuario_auth = $req->getAttribute('usuarioId');

        if ($usuario_auth != $usuario) {
            return ResponseUtil::crearRespuesta($res, ["error" => "No tienes permiso para ver los mazos de este usuario. Verificar que este proporcionando el ID del usuario."], 401);
        }

        try {
            $mazos = $this->mazoModel->obtenerMazosPorUsuario($usuario_auth);

            if (empty($mazos)) {
                return ResponseUtil::crearRespuesta($res, ["error" => "Este usuario no tiene mazos creados."],404);
            }

            return ResponseUtil::crearRespuesta($res, $mazos);

        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al obtener los mazos: " . $e->getMessage()], 500);
        }
    }

    public function actualizarMazo(Request $req, Response $res, Array $args)
    {
        $data = $req->getParsedBody();
        $usuario_auth = $req->getAttribute('usuarioId');
        $mazo_id = $args['mazo'];
        $nombre = $data['nombre'];

        if(empty($nombre)){
            return ResponseUtil::crearRespuesta($res,["error" => "El nombre del mazo es requerido"],400);
        }
 
        if (!$this->mazoModel->mazoPerteneceAUsuario($mazo_id, $usuario_auth)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "El mazo no existe o no pertenece al usuario."], 403);
        }

        try{
            $actualizo = $this->mazoModel->actualizarNombreMazo($mazo_id,$nombre);
            if(!$actualizo){
                return ResponseUtil::crearRespuesta($res,["error" => "No se pudo actualizar el mazo"],400);
            }
            return ResponseUtil::crearRespuesta($res,["mensaje" => "Mazo actualizado con exito"]);
        }catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al actualizar: " . $e->getMessage()], 500);
        }
    }

    public function listarCartasDelMazo(Request $req, Response $res, Array $args){
        $mazoId = $args['mazo'];
        $usuario_auth = $req->getAttribute('usuarioId');

        try{
            $auth = $this->mazoModel->mazoPerteneceAUsuario($mazoId,$usuario_auth);
            if(!$auth){
                return ResponseUtil::crearRespuesta($res, ["error" => 'No tenes permisos para ver las cartas de este mazo'], 401);
            }
            $cartas = $this->mazoModel->buscarCartasDelMazo($mazoId);

            if (empty($cartas)) {
                return ResponseUtil::crearRespuesta($res, ['mensaje' => 'No existen cartas en este mazo.']);
            }
            return ResponseUtil::crearRespuesta($res, $cartas);

        } catch (\Exception $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => $e->getMessage()], 500);
        }
    }
}
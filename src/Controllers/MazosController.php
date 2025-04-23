<?php
namespace App\Controllers;

use App\Models\MazoModel;
use App\Utils\ResponseUtil;


class MazosController{
    private $mazoModel;

    public function __construct(){
        $this->mazoModel = new MazoModel();
    }

    public function crearMazo($req, $res)
    {
        $data = $req->getParsedBody();
        $ids = $data['ids'];
        $nombre = $data['nombre'];
        $usuario = $req->getAttribute('usuarioId');

        if (!$ids || !is_array($ids)) {
            return ResponseUtil::crearRespuesta($res, ['error' => 'Se debe proporcionar un array de IDs de cartas.'], 400);
        }

        if (empty($nombre)) {
            return ResponseUtil::crearRespuesta($res, ['error' => 'El nombre del mazo es obligatorio.'], 400);
        }        

        if (!$this->mazoModel->cartasValidas($ids)) {
            return ResponseUtil::crearRespuesta($res, ['error' => 'Máximo 5 cartas distintas y todas deben existir'], 400);
        }

        if ($this->mazoModel->contarMazosDeUsuario($usuario) >= 3) {
            return ResponseUtil::crearRespuesta($res, ['error' => 'Máximo de mazos alcanzado'], 400);
        }

        try {
            $mazoId = $this->mazoModel->crearMazo($usuario, $nombre);
            $this->mazoModel->insertarCartasEnMazo($mazoId, $ids);

            return ResponseUtil::crearRespuesta($res, ['mensaje' => "Mazo creado con ID $mazoId, nombre: $nombre"],201);
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => $e->getMessage()], 500);
        }
    }

    public function borrarMazo($req, $res, $args)
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

    public function listadoMazos($req, $res, $args)
    {
        $usuario = $args['usuario'];
        $usuario_auth = $req->getAttribute('usuarioId');

        if ($usuario_auth != $usuario) {
            return ResponseUtil::crearRespuesta($res, ["error" => "No tienes permiso para ver los mazos de este usuario. Verificar que este proporcionando el ID del usuario."], 401);
        }

        try {
            $mazos = $this->mazoModel->obtenerMazosPorUsuario($usuario_auth);

            if (empty($mazos)) {
                return ResponseUtil::crearRespuesta($res, ["mensaje" => "Este usuario no tiene mazos creados."],400);
            }

            return ResponseUtil::crearRespuesta($res, $mazos);

        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al obtener los mazos: " . $e->getMessage()], 500);
        }
    }

    public function actualizarMazo($req,$res,$args)
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
}
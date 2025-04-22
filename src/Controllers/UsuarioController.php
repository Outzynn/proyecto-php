<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Utils\ValidationUtil;
use App\Utils\ResponseUtil;
use App\Models\UsuarioModel;

class UsuarioController {
    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    public function editarUsuario(Request $request, Response $res, array $args): Response {
        $usuario = $args['usuario'];
        $data = $request->getParsedBody();
        $nombre = $data['nombre'] ?? null;
        $password = $data['password'] ?? null;
        $usuarioId = $request->getAttribute('usuarioId');

        if (!$this->usuarioModel->validarPermisos($usuario, $usuarioId)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "No tienes permiso para editar este usuario"], 401);
        }

        if (empty($nombre) || empty($password)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Faltan datos: nombre y password nuevos son requeridos"], 400);
        }

        if (!ValidationUtil::validarClave($password)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "La contraseña debe tener por lo menos 8 caracteres y contener mayúsculas, minúsculas, números y caracteres especiales."], 400);
        }

        try {
            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $this->usuarioModel->editarUsuario($usuarioId, $nombre, $passwordHash);

            return ResponseUtil::crearRespuesta($res, ["mensaje" => "Usuario actualizado correctamente"]);
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error al actualizar el usuario: " . $e->getMessage()], 500);
        }
    }

    public function obtenerUsuario(Request $request, Response $res, array $args): Response {
        $usuario = $args['usuario'];
        $usuarioId = $request->getAttribute('usuarioId');

        if (!$this->usuarioModel->validarPermisos($usuario, $usuarioId)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "No tienes permiso para ver la información de este usuario"], 401);
        }

        try {
            $usuarioData = $this->usuarioModel->obtenerUsuarioPorId($usuarioId);
            if (!$usuarioData) {
                return ResponseUtil::crearRespuesta($res, ["error" => "Usuario no encontrado"], 404);
            }

            return ResponseUtil::crearRespuesta($res, $usuarioData);
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error al obtener el usuario: " . $e->getMessage()], 500);
        }
    }
}

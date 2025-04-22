<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Utils\ResponseUtil;
use App\Utils\ValidationUtil;
use App\Models\UsuarioModel;

class RegisterController {

    private $usuarioModel;

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    public function registrarUsuario(Request $req, Response $res) {
        $data = $req->getParsedBody();
        $nombre = $data['nombre'] ?? '';
        $usuario = $data['usuario'] ?? '';
        $password = $data['password'] ?? '';

        if (empty($nombre) || empty($usuario) || empty($password)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Faltan datos. Nombre, usuario y password son requeridos."], 400);
        }

        if (!ValidationUtil::validarUsuario($usuario)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "El usuario debe contener entre 6 y 20 caracteres, y deben ser únicamente alfanuméricos."], 400);
        }

        if (!ValidationUtil::validarClave($password)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "La contraseña debe tener por lo menos 8 caracteres y contener mayúsculas, minúsculas, números y caracteres especiales."], 400);
        }

        try {
            if ($this->usuarioModel->existeUsuario($usuario)) {
                return ResponseUtil::crearRespuesta($res, ["error" => "El nombre de usuario ya está registrado."], 409);
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);
            $this->usuarioModel->registrarUsuario($nombre, $usuario, $passwordHash);

            return ResponseUtil::crearRespuesta($res, ["mensaje" => "Usuario registrado exitosamente."], 201);
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error al registrar el usuario: " . $e->getMessage()], 500);
        }
    }
}

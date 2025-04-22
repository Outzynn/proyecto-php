<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Utils\ResponseUtil;
use App\Models\UsuarioModel;

class LoginController {
    private $usuarioModel;
    private const SECRET_KEY = 'clave_para_token';

    public function __construct() {
        $this->usuarioModel = new UsuarioModel();
    }

    public function logearUsuario(Request $req, Response $res) {
        $data = $req->getParsedBody();
        $nombre = $data['nombre'] ?? null;
        $usuario = $data['usuario'] ?? null;
        $password = $data['password'] ?? null;

        if (!$nombre || !$usuario || !$password) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Faltan datos. Nombre, usuario y password son requeridos."], 400);
        }

        try {
            $usuarioEncontrado = $this->usuarioModel->obtenerUsuarioPorNombreYUsuario($nombre, $usuario);

            if ($usuarioEncontrado && password_verify($password, $usuarioEncontrado['password'])) {
                $expiracion = time() + 3600;

                if ($usuarioEncontrado['token'] && strtotime($usuarioEncontrado['vencimiento_token']) > time()) {
                    $token = $usuarioEncontrado['token'];
                    $expiracion = strtotime($usuarioEncontrado['vencimiento_token']);
                } else {
                    $payload = [
                        "sub" => $usuarioEncontrado['id'],
                        "iat" => time(),
                        "exp" => $expiracion
                    ];
                    $token = JWT::encode($payload, self::SECRET_KEY, 'HS256');

                    $this->usuarioModel->actualizarToken($usuarioEncontrado['id'], $token, $expiracion);
                }

                return ResponseUtil::crearRespuesta($res, [
                    "token" => $token,
                    "expiracion" => date('Y-m-d H:i:s', $expiracion)
                ]);
            } else {
                return ResponseUtil::crearRespuesta($res, ["error" => "Credenciales incorrectas"], 401);
            }
        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error en la base de datos: " . $e->getMessage()], 500);
        }
    }
}

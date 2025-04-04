<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;
use App\Utils\DataBase;
use App\Utils\ResponseUtil;

use PDO;

class LoginController {

    private $pdo;
    private const SECRET_KEY = 'clave_para_token';

    public function __construct() {
        $this->pdo = Database::getInstance(); //Conecto a la BD
    }

    public function logearUsuario(Request $req, Response $res) {
        $data = $req->getParsedBody(); //Obtengo los datos del cuerpo JSON
        $nombre = $data['nombre'];
        $usuario = $data['usuario'];
        $password = $data['password'];

        if (empty($nombre) || empty($usuario) || empty($password)) { //Verifico si los campos no estan vacios
            return ResponseUtil::crearRespuesta($res,["error" => "Faltan datos. Nombre, usuario y password son requeridos."],400);
        }

        try {
            $stmt = $this->pdo->prepare("SELECT id, password, token, vencimiento_token FROM usuario WHERE usuario = :usuario AND nombre = :nombre");
            $stmt->execute([
                ':usuario' => $usuario,
                ':nombre' => $nombre,
            ]);
            $usuarioEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si el usuario existe y la password coincide
            if ($usuarioEncontrado && password_verify($password, $usuarioEncontrado['password'])) { //La password de la BD esta hasheada, pero la que recibimos en la solicitud no, por eso usamos passwordverify() para compararlas.

                if ($usuarioEncontrado['token'] && strtotime($usuarioEncontrado['vencimiento_token']) > time()) { // Aca verificamos si se ha generado un token y aun tiene validez en el tiempo actual

                    //el token aun es valido por lo tanto no creamos otro
                    $expiracion = strtotime($usuarioEncontrado['vencimiento_token']);
                    $token = $usuarioEncontrado['token'];

                } else {
                    
                    //el token se expiro o nunca fue creado, hay que crear uno 
                    $expiracion = time() + 3600;
                    $payload = [
                        "sub" => $usuarioEncontrado['id'],
                        "iat" => time(),
                        "exp" => $expiracion
                    ];
                    $token = JWT::encode($payload, self::SECRET_KEY, 'HS256'); //encodeamos el token, para que no sean visible los datos (como el ID)

                    // Guardar el token y la fecha de expiración en la base de datos
                    $stmt = $this->pdo->prepare("UPDATE usuario SET token = :token, vencimiento_token = FROM_UNIXTIME(:exp) WHERE id = :id");
                    $stmt->execute([
                        ':token' => $token,
                        ':exp' => $expiracion,
                        ':id' => $usuarioEncontrado['id']
                    ]);
                }

                // Devolver el token al usuario
                return ResponseUtil::crearRespuesta($res, ["token" => $token, "expiracion" => date('Y-m-d H:i:s', $expiracion)]);
            } else {
                // Credenciales incorrectas
                return ResponseUtil::crearRespuesta($res,["error" => "Credenciales incorrectas"],401);
            }

        } catch (\PDOException $e) {
            // Error en la base de datos
            return ResponseUtil::crearRespuesta($res,["error" => "Error en la base de datos: " . $e->getMessage()],500);
        }
    }
}
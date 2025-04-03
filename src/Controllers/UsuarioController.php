<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Utils\DataBase;
use App\Utils\ValidationUtil;
use App\Utils\ResponseUtil;

use PDO;

class UsuarioController {
    private $pdo;
    private const SECRET_KEY = 'clave_para_token';

    public function __construct() {
        $this->pdo = DataBase::getInstance();
    }

    public function editarUsuario(Request $request, Response $res, array $args): Response {
        $usuario = $args['usuario']; // Obtener el 'usuario' de los argumentos de la ruta, o sea del /usuarios/{usuario}
        $data = $request->getParsedBody(); // Obtener los datos del cuerpo de la solicitud (JSON)
        $nombre = $data['nombre'] ?? null;
        $password = $data['password'] ?? null;
        $usuarioId = $request->getAttribute('usuarioId'); // Obtener el ID del usuario del middleware, que obtube del token pasado por header

        // Validar que el usuario del token coincida con el usuario de la URL {usuario}

        if (!$this->validarPermisos($usuario,$usuarioId)) {  //Verifica si el usuario que me pasa por URL es el mismo que el del token
            return ResponseUtil::crearRespuesta($res, ["error" => "No tienes permiso para editar este usuario"], 401);
        }

        // Validar los datos recibidos
        if (empty($nombre) || empty($password)) {
            return ResponseUtil::crearRespuesta($res,["error" => "Faltan datos: nombre y password nuevos son requeridos"],400);
        }

         if(!ValidationUtil::validarClave($password)){
            return ResponseUtil::crearRespuesta($res,["error" => "La contraseña debe tener por lo menos 8 caracteres y contener mayusculas, minusculas, numeros y caracteres especiales."],400);
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT); //Hasheo la nueva password para actualizarla en la BD

        try {
            
            // Actualizar la información del usuario en la base de datos
            $stmt = $this->pdo->prepare("UPDATE usuario SET nombre = :nombre, password = :password WHERE id = :id");
            $stmt->execute([
                ':nombre' => $nombre,
                ':password' => $passwordHash,
                ':id' => $usuarioId
            ]);

            return ResponseUtil::crearRespuesta($res,["mensaje" => "Usuario actualizado correctamente"]);

        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res,["error" => "Error al actualizar el usuario: " . $e->getMessage()],500);
        }
    }


    public function obtenerUsuario(Request $request, Response $res, array $args): Response {
        $usuario = $args['usuario']; //Toma el usuario de la URL {usuario}
        $usuarioId = $request->getAttribute('usuarioId'); // Obtener el ID del usuario del middleware

        // Validar que el usuario del token coincida con el usuario de la URL

        if (!$this->validarPermisos($usuario,$usuarioId)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "No tienes permiso para ver la informacion de este usuario"], 401);
        }

        try {
            // Obtener la información del usuario de la base de datos
            $stmt = $this->pdo->prepare("SELECT id, usuario, nombre FROM usuario WHERE id = :id");
            $stmt->execute([':id' => $usuarioId]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

            // Verificar si se encontró el usuario
            if (!$usuario) {
                return ResponseUtil::crearRespuesta($res, ["error" => "Usuario no encontrado"], 404);
            }
            //En caso de no entrar en el if anterior, devuelve los datos al cliente.
            return ResponseUtil::crearRespuesta($res, $usuario);

        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error al obtener el usuario: " . $e->getMessage()],500);
        }
    }
    
    
    private function validarPermisos($usuario,$usuarioId) : bool{
        $stmt = $this->pdo->prepare("SELECT usuario FROM usuario WHERE id = :id");
        $stmt->execute([':id' => $usuarioId]);
        $usuarioEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);

        return ($usuarioEncontrado && $usuarioEncontrado['usuario'] === $usuario);
    }
}
?>
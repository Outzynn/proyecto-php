<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use PDO;

use App\Utils\DataBase;
use App\Utils\ValidationUtil;
use App\Utils\ResponseUtil;

class RegisterController {

    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance(); //Conectamos a la DB
    }

    public function registrarUsuario(Request $req, Response $res) {
        $data = $req->getParsedBody(); //Obtenemos datos desde el cuerpo del JSON
        $nombre = $data['nombre'] ?? '';
        $usuario = $data['usuario'] ?? '';
        $password = $data['password'] ?? '';

        // Validar que no haya campos vacíos
        if (empty($nombre) || empty($usuario) || empty($password)) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Faltan datos. Nombre, usuario y password son requeridos."], 400);
        }

        if(!ValidationUtil::validarUsuario($usuario)){
            return ResponseUtil::crearRespuesta($res, ["error" => "El usuario debe contener entre 6 y 20 caracteres, y deben ser unicamente alfanumericos."], 400);
        }
        
        if(!ValidationUtil::validarClave($password)){
            return ResponseUtil::crearRespuesta($res, ["error" => "La contraseña debe tener por lo menos 8 caracteres y contener mayusculas, minusculas, numeros y caracteres especiales."], 400);
        }

        try {
            // Verificar si el nombre de usuario ya está registrado
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuario WHERE usuario = :usuario"); //Esto cuenta la cantidad de usuarios que hay con el usuario que nos pasaron
            $stmt->execute([':usuario' => $usuario]);
            $existe = $stmt->fetchColumn();
            
            if ($existe > 0) { //si hay mas de 0, significa que a existe un usuario con ese nombre de usuario
                return ResponseUtil::crearRespuesta($res, ["error" => "El nombre de usuario ya está registrado."], 409);
            }

            $passwordHash = password_hash($password, PASSWORD_DEFAULT); //Hasheamos la password para que no sea visible en la base de datos.

            // Preparar y ejecutar la consulta SQL para insertar el usuario
            $sql = "INSERT INTO usuario (nombre, usuario, password) 
                    VALUES (:nombre, :usuario, :password)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':nombre' => $nombre,
                ':usuario' => $usuario,
                ':password' => $passwordHash
            ]);

            // Enviar respuesta de éxito
            return ResponseUtil::crearRespuesta($res, ["mensaje" => "Usuario registrado exitosamente."], 201);

        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error al registrar el usuario: " . $e->getMessage()], 500);
        }
    }
}

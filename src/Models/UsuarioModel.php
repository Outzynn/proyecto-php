<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class UsuarioModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function existeUsuario($usuario) {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuario WHERE usuario = :usuario");
        $stmt->execute([':usuario' => $usuario]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function registrarUsuario($nombre, $usuario, $passwordHash) {
        $stmt = $this->pdo->prepare("INSERT INTO usuario (nombre, usuario, password) VALUES (:nombre, :usuario, :password)");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':usuario' => $usuario,
            ':password' => $passwordHash
        ]);
    }
    
    public function obtenerUsuarioPorNombreYUsuario($nombre, $usuario) {
        $stmt = $this->pdo->prepare("SELECT id, password, token, vencimiento_token FROM usuario WHERE usuario = :usuario AND nombre = :nombre");
        $stmt->execute([
            ':usuario' => $usuario,
            ':nombre' => $nombre,
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function actualizarToken($id, $token, $expiracion) {
        $stmt = $this->pdo->prepare("UPDATE usuario SET token = :token, vencimiento_token = FROM_UNIXTIME(:exp) WHERE id = :id");
        return $stmt->execute([
            ':token' => $token,
            ':exp' => $expiracion,
            ':id' => $id
        ]);
    }
    public function obtenerUsuarioPorId($id) {
        $stmt = $this->pdo->prepare("SELECT id, usuario, nombre FROM usuario WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function editarUsuario($id, $nombre, $passwordHash) {
        $stmt = $this->pdo->prepare("UPDATE usuario SET nombre = :nombre, password = :password WHERE id = :id");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':password' => $passwordHash,
            ':id' => $id
        ]);
    }
    
    public function validarPermisos($usuario, $id) {
        $stmt = $this->pdo->prepare("SELECT usuario FROM usuario WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $usuarioEncontrado = $stmt->fetch(PDO::FETCH_ASSOC);
    
        return ($usuarioEncontrado && $usuarioEncontrado['usuario'] === $usuario);
    }
    
}

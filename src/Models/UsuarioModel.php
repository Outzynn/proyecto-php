<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class UsuarioModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function existeUsuario(string $usuario): bool {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM usuario WHERE usuario = :usuario");
        $stmt->execute([':usuario' => $usuario]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function registrarUsuario(string $nombre, string $usuario, string $passwordHash): bool{
        $stmt = $this->pdo->prepare("INSERT INTO usuario (nombre, usuario, password) VALUES (:nombre, :usuario, :password)");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':usuario' => $usuario,
            ':password' => $passwordHash
        ]);
    }
    
    public function obtenerUsuarioPorUsuario(string $usuario): ?array {
        $stmt = $this->pdo->prepare("SELECT id, nombre, password, token, vencimiento_token FROM usuario WHERE usuario = :usuario");
        $stmt->execute([
            ':usuario' => $usuario,
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        return $resultado === false ? null : $resultado;
    }

    public function actualizarToken(int $id, string $token, int $expiracion): bool{
        $stmt = $this->pdo->prepare("UPDATE usuario SET token = :token, vencimiento_token = FROM_UNIXTIME(:exp) WHERE id = :id");
        return $stmt->execute([
            ':token' => $token,
            ':exp' => $expiracion,
            ':id' => $id
        ]);
    }
    public function obtenerUsuarioPorId(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT id, usuario, nombre FROM usuario WHERE id = :id");
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function editarUsuario(int $id, string $nombre, string $passwordHash): bool {
        $stmt = $this->pdo->prepare("UPDATE usuario SET nombre = :nombre, password = :password WHERE id = :id");
        return $stmt->execute([
            ':nombre' => $nombre,
            ':password' => $passwordHash,
            ':id' => $id
        ]);
    }
    
}

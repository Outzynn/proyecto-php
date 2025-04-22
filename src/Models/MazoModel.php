<?php
namespace App\Models;

use App\Utils\Database;
use PDO;

class MazoModel {
    private $pdo;

    public function __construct() {
        $this->pdo = Database::getInstance();
    }

    public function crearMazo($usuarioId, $nombre) {
        $sql = "INSERT INTO mazo (usuario_id, nombre) VALUES (:usuario_id, :nombre)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuario_id' => $usuarioId,
            ':nombre' => $nombre
        ]);
        return $this->pdo->lastInsertId();
    }

    public function insertarCartasEnMazo($mazoId, $ids) {
        $values = array_map(fn($id) => "($mazoId, $id, 'en_mazo')", $ids);
        $sql = "INSERT INTO mazo_carta (mazo_id, carta_id, estado) VALUES " . implode(', ', $values);
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
    }

    public function contarMazosDeUsuario($usuarioId) {
        $sql = "SELECT COUNT(*) FROM mazo WHERE usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuarioId]);
        return $stmt->fetchColumn();
    }

    public function cartasValidas($ids) {
        if (count($ids) > 5 || count($ids) !== count(array_unique($ids))) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT id FROM carta WHERE id IN ($placeholders)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($ids);
        $result = $stmt->fetchAll(PDO::FETCH_COLUMN);

        return count($result) === count($ids);
    }

    public function mazoPerteneceAUsuario($mazoId, $usuarioId) {
        $sql = "SELECT COUNT(*) FROM mazo WHERE id = :mazo_id AND usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':mazo_id' => $mazoId,
            ':usuario_id' => $usuarioId
        ]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function mazoEnUso($mazoId) {
        $sql = "SELECT COUNT(*) FROM partida WHERE mazo_id = :mazo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mazo_id' => $mazoId]);
        return $stmt->fetchColumn() > 0;
    }
    
    public function eliminarMazo($mazoId) {
        $sql = "DELETE FROM mazo WHERE id = :mazo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mazo_id' => $mazoId]);
        return $stmt->rowCount() > 0;
    }    

    public function obtenerMazosPorUsuario($usuarioId) {
        $sql = "SELECT id, nombre FROM mazo WHERE usuario_id = :usuarioId";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':usuarioId' => $usuarioId]);
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    }
    
    public function actualizarNombreMazo($mazo_id,$nombre){
        $sql = "UPDATE mazo SET nombre = :nombre WHERE id = :mazo_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'nombre' => $nombre,
                'mazo_id' => $mazo_id
            ]);

            return $stmt->rowCount() > 0;
    }
    
}

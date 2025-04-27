<?php
namespace App\Models;

use PDO;
use App\Utils\DataBase;

class PartidaModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getInstance();
    }

    public function mazoPerteneceAlUsuario(int $mazoId, int $usuarioId): bool {
        $sql = "SELECT usuario_id FROM mazo WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $mazoId]);
        $idEncontrado = $stmt->fetchColumn();

        return (int)$idEncontrado === $usuarioId;
    }

    public function crearPartida(int $usuarioId, int $mazoId): int {
        $sql = "INSERT INTO partida (usuario_id, fecha, mazo_id, estado) VALUES (:usuarioId, NOW(), :mazoId, :estado)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuarioId' => $usuarioId,
            ':mazoId' => $mazoId,
            ':estado' => "en_curso"
        ]);
        return $this->pdo->lastInsertId();
    }

    public function ponerCartasEnMano(int $mazoId): void {
        $sql = "UPDATE mazo_carta SET estado = 'en_mano' WHERE mazo_id = :idMazo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idMazo' => $mazoId]);
    }

    public function obtenerCartasDelMazo(int $mazoId): array {
        $sql = "SELECT carta_id FROM mazo_carta WHERE mazo_id = :idMazo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idMazo' => $mazoId]);
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    public function obtenerEstadisticas() {
        // Consulta SQL para obtener la cantidad de partidas ganadas, perdidas y empatadas
        $sql = "
            SELECT 
                usuario_id,
                SUM(CASE WHEN el_usuario = 'gano' THEN 1 ELSE 0 END) AS partidas_ganadas,
                SUM(CASE WHEN el_usuario = 'perdio' THEN 1 ELSE 0 END) AS partidas_perdidas,
                SUM(CASE WHEN el_usuario = 'empato' THEN 1 ELSE 0 END) AS partidas_empatadas
            FROM partida
            GROUP BY usuario_id
        ";

        // Ejecutamos la consulta
        $stmt = $this->pdo->query($sql);

        // Retornamos los resultados
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

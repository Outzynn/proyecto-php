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

    public function mazoEnUso(int $id_mazo): bool
    {
        $sql = "SELECT COUNT(*) FROM partida WHERE mazo_id = :mazo_id AND estado = :estado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':mazo_id' => $id_mazo,
            ':estado' => "en_curso"
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }

    public function crearPartida(int $usuarioId, int $mazoId): int {
        $sql = "INSERT INTO partida (usuario_id, fecha, mazo_id, estado) VALUES (:usuarioId, NOW(), :mazoId, :estado)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':usuarioId' => $usuarioId,
            ':mazoId' => $mazoId,
            ':estado' => "en_curso"
        ]);
        return (int)$this->pdo->lastInsertId();
    }

    public function ponerCartasEnMano(int $mazoId): void {
        $sql = "UPDATE mazo_carta SET estado = 'en_mano' WHERE mazo_id = :idMazo";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':idMazo' => $mazoId]);
    }

    public function obtenerCartasDelMazo(int $mazo_id){
        $sql = "SELECT 
                    c.id, c.nombre, c.ataque, c.ataque_nombre, 
                    a.nombre AS atributo_nombre
                FROM carta c
                JOIN mazo_carta mc ON c.id = mc.carta_id
                JOIN atributo a ON c.atributo_id = a.id
                WHERE mc.mazo_id = :mazoId
        ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'mazoId' => $mazo_id
        ]);

        $cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $cartas;

    }

    public function obtenerEstadisticas(): array{
        $sql = "
                SELECT 
                u.nombre,
                SUM(CASE WHEN p.el_usuario = 'gano' THEN 1 ELSE 0 END) AS partidas_ganadas,
                SUM(CASE WHEN p.el_usuario = 'perdio' THEN 1 ELSE 0 END) AS partidas_perdidas,
                SUM(CASE WHEN p.el_usuario = 'empato' THEN 1 ELSE 0 END) AS partidas_empatadas
            FROM partida p
            JOIN usuario u ON p.usuario_id = u.id
            GROUP BY u.nombre
        ";
        $stmt = $this->pdo->query($sql);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function idPartidaEnCurso(): int {
        $sql = "SELECT id FROM partida WHERE estado = :estado LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':estado' => "en_curso"
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resultado && isset($resultado['id'])) {
            return (int) $resultado['id'];
        }
        else{
            return 0;
        }
    }

    public function partidaEnCurso(): bool {
        $sql = " SELECT COUNT(*) FROM partida WHERE estado = :estado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':estado' => "en_curso"
        ]);
        return $stmt->fetchColumn() > 0;
    }

    public function duenioDeLaPartida():int {
        $sql = "SELECT usuario_id FROM partida WHERE estado = :estado LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':estado' => "en_curso"
        ]);
        $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resultado && isset($resultado['usuario_id'])) {
            return (int) $resultado['usuario_id'];
        }
        else{
            return 0;
        }
    }

    public function mazoDeLaPartida(){
        $sql = "SELECT mazo_id FROM partida WHERE estado = :estado LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':estado' => "en_curso"
        ]);
        $respuesta = $stmt->fetch(PDO::FETCH_ASSOC);
        if($respuesta){
            return $respuesta["mazo_id"];
        }
        return null;
    }
}


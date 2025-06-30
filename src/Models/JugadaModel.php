<?php
namespace App\Models;
use App\Utils\Database;
use PDO;

class JugadaModel{
    private $pdo;

    public function __construct(){
        $this->pdo = DataBase::getInstance();
    }

    public function partidaValida(int $id_partida):bool
    {
        $sql = "SELECT COUNT(*) FROM partida where id = :id AND estado = :estado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id_partida,
            ':estado' => "en_curso"
        ]);
        return (int)$stmt->fetchColumn() > 0;
    }
    public function validarPermisos(int $mazo_id, int $usuario_id):bool
    {
        $sql = "SELECT usuario_id FROM mazo WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $mazo_id
        ]);

        $usuario_del_mazo = $stmt->fetchColumn();

        return ((int)$usuario_id === (int)$usuario_del_mazo);
    }

    public function cartaValida(int $carta_id, int $mazo_id):bool
    {
        $sql = "SELECT COUNT(*) FROM mazo_carta WHERE carta_id = :carta_id AND estado = :estado AND mazo_id = :mazo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':carta_id' => $carta_id,
            ':estado' => "en_mano",
            ':mazo_id' => $mazo_id
        ]);
        $valida = $stmt->fetchColumn();
        return ((int)$valida === 1);
    }

    public function jugadaServidor(int $mazo_id): ?int {

        $sql = "SELECT carta_id FROM mazo_carta WHERE mazo_id = :mazo_id AND estado = 'en_mano' ORDER BY RAND() LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':mazo_id' => $mazo_id
        ]);
        $id= (int)$stmt->fetchColumn();

        if (!$id) {
            return null;
        }

        $sql = "UPDATE mazo_carta SET estado = 'descartado' WHERE carta_id = :id AND mazo_id = :mazo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':mazo_id' => MAZO_SERVIDOR
        ]);

        return (int) $id;
    }

    public function obtenerInfoCarta(int $carta_id):array
    {
        $sql = "SELECT id,nombre,ataque,ataque_nombre,atributo_id FROM carta WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $carta_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hayVentaja(int $atributo_1, int $atributo_2):bool
    {
        $sql = "SELECT COUNT(*) FROM gana_a WHERE atributo_id = :atributo_1 AND atributo_id2 = :atributo_2";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':atributo_1' => $atributo_1,
            ':atributo_2' => $atributo_2,
        ]);
        return $stmt->fetchColumn() > 0;
    }

    public function buscarMazo(int $partida_id): ?int
    {
        $sql = "SELECT mazo_id FROM partida WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $partida_id
        ]);
        return (int)$stmt->fetchColumn();
    }

    public function actualizarCarta(int $mazo_id, int $carta_id, string $estado)
    {
        $sql = "UPDATE mazo_carta SET estado = :estado WHERE carta_id  = :carta_id AND mazo_id = :mazo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':carta_id' => $carta_id,
            ':mazo_id' => $mazo_id,
            ':estado' => $estado
        ]);
        return $stmt->rowCount() > 0;
    }

    public function crearJugada(int $partida_id, int $carta_servidor, int $carta_id, string $resultado):bool
    {
        $sql = "INSERT INTO jugada (partida_id,carta_id_a,carta_id_b,el_usuario) 
        VALUES (:partida_id,:carta_servidor,:carta_id,:resultado)";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':partida_id' => $partida_id,
            ':carta_servidor' => $carta_servidor,
            ':carta_id' => $carta_id,
            ':resultado' => $resultado
        ]);
        return $stmt->rowCount() > 0;
    }

    public function esUltima(int $partida_id): bool
    {
        $sql = "SELECT COUNT(*) FROM jugada WHERE partida_id = :partida_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':partida_id' => $partida_id
        ]);
        $cantidad = (int)$stmt->fetchColumn();

        return ($cantidad === 5);
    }

    public function guardarCartasEnMazo(int $mazo_id): bool
    {
        $sql = "UPDATE mazo_carta SET estado = :estado WHERE mazo_id = :mazo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':estado' => "en_mazo",
            ':mazo_id' => $mazo_id
        ]);
        return $stmt->rowCount() > 0;
    }

    public function resultadoPartida(int $partida_id): string  //esto se podria mejorar para que el model no incluya logica y que de eso se encargue el controller.
    {
        $sql = "SELECT COUNT(*) FROM jugada WHERE partida_id = :partida_id AND el_usuario = :estado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':partida_id' => $partida_id,
            ':estado' => "gano"
        ]);
        $gano_cant = (int)$stmt->fetchColumn();

        $sql = "SELECT COUNT(*) FROM jugada WHERE partida_id = :partida_id AND el_usuario = :estado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':partida_id' => $partida_id,
            ':estado' => "perdio"
        ]);
        $perdio_cant = (int)$stmt->fetchColumn();

        if($gano_cant > $perdio_cant){
            $el_usuario = "gano";
        }
        else{
            if($gano_cant < $perdio_cant){
                 $el_usuario = "perdio";
            }
            else{
                $el_usuario = "empato";
            }
        }
        return $el_usuario;
    }

    public function actualizarPartida(int $partida_id, string $resultado): void
    {
        $sql = "UPDATE partida SET estado = :estado, el_usuario = :el_usuario WHERE id = :partida_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':partida_id' => $partida_id,
            ':estado' => "finalizada",
            ':el_usuario' => $resultado
        ]);
    }

    public function obtenerCartasEnMano(int $mazo_id): array
    {
        $sql = "SELECT 
                c.id,
                c.nombre,
                c.ataque,
                c.ataque_nombre,
                a.nombre AS atributo_nombre
            FROM carta c
            JOIN mazo_carta mc ON c.id = mc.carta_id
            JOIN atributo a ON c.atributo_id = a.id
            WHERE mc.mazo_id = :mazo_id
              AND mc.estado = 'en_mano'";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':mazo_id' => $mazo_id]);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    public function pertenecePartida(int $user_id,  int $partida_id):bool
    {
        $sql = "SELECT COUNT(*) FROM partida WHERE id = :id AND usuario_id = :usuario_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $partida_id,
            ':usuario_id' => $user_id
        ]);
        return $stmt->fetchColumn() > 0;
    }
}
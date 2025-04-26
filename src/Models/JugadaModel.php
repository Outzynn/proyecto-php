<?php
namespace App\Models;
use App\Utils\Database;
use PDO;

class JugadaModel{
    private $pdo;

    public function __construct(){
        $this->pdo = DataBase::getInstance();
    }

    public function validarPermisos($partida_id,$usuario_id){
        $sql = "SELECT mazo_id FROM partida where id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $partida_id
        ]);
        
        $mazo_id = $stmt->fetchColumn();

        $sql = "SELECT usuario_id FROM mazo WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $mazo_id
        ]);

        $usuario_del_mazo = $stmt->fetchColumn();

        return ((int)$usuario_id === (int)$usuario_del_mazo);
    }

    public function cartaValida($carta_id, $partida_id){
        $sql = "SELECT mazo_id FROM partida where id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $partida_id
        ]);
        
        $mazo_id = (int)$stmt->fetchColumn();

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

    public function jugadaServidor(): ?int {

        $sql = "SELECT carta_id FROM mazo_carta WHERE mazo_id = 1 AND estado = 'en_mazo' ORDER BY RAND() LIMIT 1";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $id= (int)$stmt->fetchColumn();

        if (!$id) {
            return null;
        }

        $sql = "UPDATE mazo_carta SET estado = 'descartado' WHERE carta_id = :id AND mazo_id = 1 ";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return (int) $id;
    }

    public function obtenerInfoCarta($carta_id)
    {
        $sql = "SELECT id,nombre,ataque,ataque_nombre,atributo_id FROM carta WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $carta_id
        ]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function hayVentaja($atributo_1,$atributo_2)
    {
        $sql = "SELECT COUNT(*) FROM gana_a WHERE atributo_id = :atributo_1 AND atributo_id2 = :atributo_2";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':atributo_id' => $atributo_1,
            ':atributo_id2' => $atributo_2,
        ]);
        return $stmt->fetchColumn() > 0;
    }

    public function buscarMazo($partida_id)
    {
        $sql = "SELECT mazo_id FROM partida WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $partida_id
        ]);
        return (int)$stmt->fetchColumn();
    }

    public function actualizarCarta($mazo_id,$carta_id,$estado)
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

    public function crearJugada($partida_id,$carta_servidor,$carta_id,$resultado)
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

    public function esUltima($partida_id)
    {
        $sql = "SELECT COUNT(*) FROM jugada WHERE partida_id = :partida_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':partida_id' => $partida_id
        ]);
        $cantidad = (int)$stmt->fetchColumn();
        if($cantidad === 5){
            return true;
        }
        return false;
    }

    public function resultadoUsuario($partida_id)
    {
        $sql = "SELECT COUNT(*) FROM jugada WHERE partida_id = :partida_id AND el_usuario = :estado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'partida_id' => $partida_id,
            'estado' => "gano"
        ]);
        $gano_cant = (int)$stmt->fetchColumn();

        $sql = "SELECT COUNT(*) FROM jugada WHERE partida_id = :partida_id AND el_usuario = :estado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'partida_id' => $partida_id,
            'estado' => "perdio"
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

    public function actualizarPartida($partida_id,$resultado)
    {
        $sql = "UPDATE partida SET estado = :estado, el_usuario = :el_usuario WHERE id = :partida_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':partida_id' => $partida_id,
            ':estado' => "finalizado",
            ':el_usuario' => $resultado
        ]);
    }

}
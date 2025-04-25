<?php
namespace App\Models;
use App\Utils\Database;

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
        
        $mazo_id = (int)$stmt->fetchColumn();

        $sql = "SELECT usuario_id FROM mazo WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $mazo_id
        ]);

        $usuario_del_mazo = (int)$stmt->fetchColumn();

        if($usuario_id === $usuario_del_mazo){
            return true;
        }

        return false;
    }

    public function cartaValida($carta_id, $partida_id){
        $sql = "SELECT mazo_id FROM partida where id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $partida_id
        ]);
        
        $mazo_id = (int)$stmt->fetchColumn();

        $sql = "SELECT COUNT(*) FROM mazo_carta WHERE carta_id = :carta_id AND estado = :estado";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':carta_id' => $carta_id,
            ':estado' => "en_mazo"
        ]);
        $valida = (int)$stmt->fetchColumn();

        if($valida === 1){
            return true;
        }
        return false;
    }

    public function jugar($partida_id, $carta_id)
    {
        $datos = [
            "carta_servidor" => null,
            "esUltima" => false,
            "resultado" => null
        ];
        $carta_servidor = jugadaServidor();
        $resultado = gana($carta_id,$carta_servidor); //devuelve un string de "gano,perdio o empato".

        $mazo_id = buscarMazo($partida_id);
        actualizarCarta($mazo_id,$carta_id,"descartado");

        crearJugada($partida_id,$carta_servidor,$carta_id,$resultado);

        $datos["carta_servidor"] = $carta_servidor;

        if(esUltima($partida_id)){
            actualizarPartida($partida_id); //pone finalizado

            $datos["esUltima"] = true;
            $datos["resultado"] = resultadoPartida($partida_id);

        }
        
        return $datos;
    }

    public function resultadoPartida($partida_id){
        $sql = "SELECT el_usuario FROM partida WHERE id = :partida_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(
            [':partida_id' => $partida_id
        ]);
        return $stmt->fetchColumn();
    }

    public function actualizarPartida($partida_id){

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



        $sql = "UPDATE partida SET estado = :estado, el_usuario = :el_usuario WHERE id = :partida_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':partida_id' => $partida_id,
            ':estado' => "finalizado",
            ':el_usuario' => $el_usuario
        ]);


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



    public function gana($carta_user,$carta_servidor)
    {
        $sql = "SELECT ataque,atributo_id FROM carta WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $carta_user
        ]);
        $datos_user = $stmt->fetch(PDO::FETCH_ASSOC);
        $atributo_user = $datos_user['atributo_id'];
        $ataque_user = $datos_user['ataque'];

        $sql = "SELECT ataque,atributo_id FROM carta WHERE id = :id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $carta_servidor
        ]);
        $datos_servidor = $stmt->fetch(PDO::FETCH_ASSOC);
        $atributo_servidor = $datos_servidor['atributo_id'];
        $ataque_servidor = $datos_servidor['ataque'];



        $sql = "SELECT COUNT(*) FROM gana_a WHERE atributo_id = :atributo_id AND atributo_id2 = :atributo_id2";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':atributo_id' => $atributo_user,
            ':atributo_id2' => $atributo_servidor
        ]);
        $resultado1 = (int)$stmt->fetchColumn();

        if($resultado1===1){
            $atributo_resultado = "gana";
        }
        else
        {
            $sql = "SELECT COUNT(*) FROM gana_a WHERE atributo_id = :atributo_id AND atributo_id2 = :atributo_id2";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':atributo_id' => $atributo_servidor,
                ':atributo_id2' => $atributo_user
            ]);
            $resultado2 = (int)$stmt->fetchColumn();
            
            if($resultado2===1){
                $atributo_resultado = "pierde";
            }
            else{
                $atributo_resultado = "empata";
            }
        }

        if($atributo_resultado === "gana"){
            $ataque_user = $ataque_user*1.3;
        }
        else{
            $ataque_servidor = $ataque_servidor*1.3;
        }
        
        if($ataque_user > $ataque_servidor){
            return "gano";
        }
        else{
            iF($ataque_user < $ataque_servidor){
                return "perdio";
            }
            return "empato";
        }


    }

    public function jugadaServidor(): ?int { //  ?int hace que puedas devolver un null o un entero. Esta funcion necesita recibir la conexion (PDO) por parametro.

        $sql = "SELECT carta_id FROM mazo_carta WHERE mazo_id = 1 AND estado = 'en_mazo' ORDER BY RAND() LIMIT 1"; //Selecciona una carta de las disponibles, ORDER BY RAND() ordena aleatoriamente las cartas y LIMIT 1 te trae 1 sola. Esto es para traer una carta random de las disponibles.
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();
        $id= (int)$stmt->fetchColumn();

        if (!$id) {
            return null; //Devuelve null si no encontro cartas disponibles.
        }

        $sql = "UPDATE mazo_carta SET estado = 'descartado' WHERE carta_id = :id "; //Udatea el estado de la carta jugada a "descartado".
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return (int) $id; //Devuelve el id de la carta jugada.
    }
    public function puntosDeFuerza($carta_user,$carta_server)
    {
        $sql = "SELECT ataque FROM carta WHERE id = :carta_user OR id = :carta_server";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            'carta_user' => $carta_user,
            'carta_server' => $carta_server
        ]);
        $puntos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (count($puntos) === 2) {
            $suma = $puntos[0]['ataque'] + $puntos[1]['ataque'];
        } else {
            $suma = 0;
        }
        return $suma;
    }
}
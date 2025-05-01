<?php
namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use App\Models\JugadaModel;
use App\Utils\ResponseUtil;


class JugadaController{
    private $jugadaModel;

    public function __construct(){
        $this->jugadaModel = new JugadaModel();
    }

    public function jugar(Request $req, Response $res)
    {
        $data = $req->getParsedBody();
        $carta_id = $data['carta_id'];
        $partida_id = $data['partida_id'];
        $id_auth = $req->getAttribute('usuarioId');

        if(!is_numeric($carta_id) || !is_numeric($partida_id)){
            return ResponseUtil::crearRespuesta($res,["error" => "Carta o partida no validas."]);
        }

        try{
            if(!$this->jugadaModel->partidaValida($partida_id)){
                return ResponseUtil::crearRespuesta($res,["error" => "Partida no valida, o ya finalizo."],400);
            }

            $mazo_id = $this->jugadaModel->buscarMazo($partida_id);
            if(!$mazo_id){
                return ResponseUtil::crearRespuesta($res,["error" => "Mazo de la partida no encontrado."],400);
            }

            if(!$this->jugadaModel->validarPermisos($mazo_id,$id_auth)){
                return ResponseUtil::crearRespuesta($res,["error" => "Permisos no validos."],401);
            }
            if(!$this->jugadaModel->cartaValida($carta_id,$mazo_id)){
                return ResponseUtil::crearRespuesta($res,["error"=> "Carta no valida."],400);
            }

            $carta_id_servidor = $this->jugadaModel->jugadaServidor(MAZO_SERVIDOR);
            if($carta_id_servidor == null){
                return ResponseUtil::crearRespuesta($res,["error" => "Servidor ya no tiene cartas en mazo."], 400);
            }

            $carta_jugador = $this->jugadaModel->obtenerInfoCarta($carta_id);
            $carta_servidor = $this->jugadaModel->obtenerInfoCarta($carta_id_servidor);

            $puntos_jugador = $carta_jugador['ataque'];
            $puntos_servidor = $carta_servidor['ataque'];

            if ($this->jugadaModel->hayVentaja($carta_jugador['atributo_id'], $carta_servidor['atributo_id'])) {
                $puntos_jugador *= 1.3; 
            } elseif ($this->jugadaModel->hayVentaja($carta_servidor['atributo_id'], $carta_jugador['atributo_id'])) {
                $puntos_servidor *= 1.3; 
            }

            $resultado = $this->determinarResultado($puntos_jugador,$puntos_servidor);

            $mazo_id = $this->jugadaModel->buscarMazo($partida_id);

            if(!$this->jugadaModel->actualizarCarta($mazo_id,$carta_id,"descartado")){
                return ResponseUtil::crearRespuesta($res,["error" => "No se pudo descartar la carta del jugador."],400);
            }

            if(!$this->jugadaModel->crearJugada($partida_id,$carta_id_servidor,$carta_id,$resultado)){
                return ResponseUtil::crearRespuesta($res,["error" => "No se pudo crear la jugada."],400);
            }

            $respuesta = [
                'carta_jugada_por_servidor' => $carta_servidor,
                'carta_jugada_por_el_jugador' => $carta_jugador,
                'puntos_de_fuerza_carta_jugador' => $puntos_jugador,
                'puntos_de_fuerza_carta_servidor' => $puntos_servidor,
                'el usuario' => $resultado
            ];

            if($this->jugadaModel->esUltima($partida_id)){
                $this->jugadaModel->guardarCartasEnMazo($mazo_id);
                $this->jugadaModel->guardarCartasEnMazo(MAZO_SERVIDOR);
                $resultado_usuario = $this->jugadaModel->resultadoPartida($partida_id);
                $this->jugadaModel->actualizarPartida($partida_id,$resultado_usuario);
                $respuesta['gano_el_juego'] = $this->quienGano($resultado_usuario);
            }

            return ResponseUtil::crearRespuesta($res, $respuesta, 200);

        }catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error en la base de datos: " . $e->getMessage()], 500);
        }
    }

    private function determinarResultado($puntos_jugador,$puntos_servidor)
    {
        if($puntos_jugador > $puntos_servidor){
            return "gano";
        }
        elseif($puntos_jugador < $puntos_servidor){
            return "perdio";
        }
        else{
            return "empato";
        }
    }

    private function quienGano($resultado_usuario)
    {
        if($resultado_usuario == "gano"){
            return "el usuario";
        }
        elseif($resultado_usuario == "perdio"){
            return "el servidor";
        }
        else{
            return "empataron";
        }
    }

public function obtenerCartasEnMano(Request $req, Response $res, Array $args)
    {
        $usuario_id = $args['usuario'];
        $partida_id = $args['partida'];
        $usuario_auth = $req->getAttribute('usuarioId');

        if( !is_numeric($usuario_id) || !is_numeric($partida_id)){
            return ResponseUtil::crearRespuesta($res,["error" => "Usuario o partida no proporcionados o formato invalido."]);
        }
        if($usuario_id != $usuario_auth){
            return ResponseUtil::crearRespuesta($res,["error" => "No tienes permisos para obtener las cartas de este usuario."],401);
        }

        try{
            if(!$this->jugadaModel->pertenecePartida($usuario_auth,$partida_id)){
                return ResponseUtil::crearRespuesta($res,["error" => "La partida no le pertenece al usuario."],401);
            }
        
            if(!$this->jugadaModel->partidaValida($partida_id)){
                return ResponseUtil::crearRespuesta($res,["error" => "La partida no es valida."],400);
            }
            
            $mazo_id = $this->jugadaModel->buscarMazo($partida_id);
            if(!$mazo_id){
                return ResponseUtil::crearRespuesta($res,["error" => "Mazo de la partida no encontrado."],400);
            }

            $cartas_en_mano = $this->jugadaModel->obtenerCartasEnMano($mazo_id);
            if(!$cartas_en_mano){
                return ResponseUtil::crearRespuesta($res,["error" => "No hay cartas en mano."],400);
            }

            $data = [
                'mensaje' => 'Estas son las cartas en mano del usuario:',
                'cartas' => $cartas_en_mano
            ];
            return ResponseUtil::crearRespuesta($res,$data,200);

        }catch (\Exception $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => $e->getMessage()], 500);
        }
    }
}
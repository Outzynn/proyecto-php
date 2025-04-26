<?php
namespace App\Controllers;
use App\Models\JugadaModel;
use App\Utils\ResponseUtil;


class JugadaController{
    private $jugadaModel;

    public function __construct(){
        $this->jugadaModel = new JugadaModel();
    }

    public function jugar($req,$res)
    {
        $data = $req->getParsedBody();
        $carta_id = $data['carta_id'];
        $partida_id = $data['partida_id'];
        $id_auth = $req->getAttribute('usuarioId');

        if(!is_numeric($carta_id) || !is_numeric($partida_id)){
            return ResponseUtil::crearRespuesta($res,["error" => "Carta o partida no validas."]);
        }

        try{
            if(!$this->jugadaModel->validarPermisos($partida_id,$id_auth)){
                return ResponseUtil::crearRespuesta($res,["error" => "Permisos no validos."],401);
            }
            if(!$this->jugadaModel->cartaValida($carta_id,$partida_id)){
                return ResponseUtil::crearRespuesta($res,["error"=> "Carta no valida."],400);
            }

            $carta_id_servidor = $this->jugadaModel->jugadaServidor();
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
                'puntos_de_fuerza_carta_jugador' => $puntos_jugador,
                'puntos_de_fuerza_carta_servidor' => $puntos_servidor
            ];

            if($this->jugadaModel->esUltima($partida_id)){
                $resultado_usuario = $this->jugadaModel->resultadoUsuario($partida_id);
                $this->jugadaModel->actualizarPartida($partida_id,$resultado_usuario);

                $respuesta['gano_el_juego'] = $this->quienGano($resultado_usuario);
            }
        }catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error en la base de datos: " . $e->getMessage()], 500);
        }
        return ResponseUtil::crearRespuesta($res, $respuesta, 200);
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
}
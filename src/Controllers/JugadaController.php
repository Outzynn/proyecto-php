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
        $id_auth = $req->getAttribute('UsuarioId');

        try{
            if(!$this->jugadaModel->validarPermisos($partida_id,$id_auth)){
                return ResponseUtil::crearRespuesta($res,["error" => "Permisos no validos.",401]);
            }
            if(!$this->jugadaModel->cartaValida($carta_id,$partida_id)){
                return ResponseUtil::crearRespuesta($res,["error"=> "Carta no valida.",400]);
            }

            //$datos es array y tiene: -resultado -carta_servidor
            $datos = $this->jugadaModel->Jugar($partida_id,$carta_id);

            $this->jugadaModel->actualizarCarta($carta_id); //actualizo la carta a descartado en la tabla mazo_carta
            $this->jugadaModel->actualizarJugada($datos['resultado'], $datos['id_jugada']);

            
            $puntos = $this->jugadaModel->puntosDeFuerza($carta_id,$datos['carta_servidor']);

            $respuesta = [
                'carta_servidor' => $datos['carta_servidor'],
                'puntos_de_fuerza' => $puntos
            ];

            if($this->jugadaModel->ultimaJugada){
                $this->jugadaModel->cerrarPartida($partida_id);
                $gano = $this->jugadaModel->quienGano($partida_id)
                //agregar a $respuesta $gano.
            }
            return ResponseUtil::crearRespuesta($res,$respuesta);
        }


}
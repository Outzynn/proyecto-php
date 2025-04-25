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

        if(!$carta_id || !$partida_id){
            return ResponseUtil::crearRespuesta($res,["error" => "Carta o partida no enviadas."]);
        }

        try{
            if(!$this->jugadaModel->validarPermisos($partida_id,$id_auth)){
                return ResponseUtil::crearRespuesta($res,["error" => "Permisos no validos."],401);
            }
            if(!$this->jugadaModel->cartaValida($carta_id,$partida_id)){
                return ResponseUtil::crearRespuesta($res,["error"=> "Carta no valida."],400);
            }

            //$datos es array y tiene: -carta_servidor -esUltima -resultado
            $datos = $this->jugadaModel->jugar($partida_id,$carta_id);

            $carta_servidor = $datos["carta_servidor"];
            $puntosDeFuerza = $this->jugadaModel->puntosDeFuerza($carta_id,$carta_servidor);
            $respuesta = [
                "carta_servidor" => $carta_servidor,
                "puntos_de_fuerza" => $puntosDeFuerza,
                "resultado" => null
            ];

            if($datos["esUltima"]){
                $respuesta["resultado"] = $datos["resultado"];
            }

            return ResponseUtil::crearRespuesta($res,$respuesta);

        }catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Error en la base de datos: " . $e->getMessage()], 500);
        }
    }
}
<?php
namespace App\Controllers;

use App\Models\CartaModel;
use App\Utils\ResponseUtil;
use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

class CartaController {
    private $cartaModel;

    public function __construct() {
        $this->cartaModel = new CartaModel();
    }

    public function listarCartas(Request $req, Response $res, array $args): Response {  
        //mejorar para que no sea necesario pasar atributo y nombre, si no pasa nada lista todas las cartas,
        //si pasa solo atributo lista las cartas con ese atributo y si solo pasa nombre lista las cartas con ese nombre 
        // o que contengan esos caracteres. si pasan ambos, que traiga una resta entre ambos.

        $params = $req->getQueryParams(); // Obtener los parámetros de la query string
        $atributo_id = $params['atributo'] ?? null;
        $nombre = $params['nombre'] ?? null;

        try {
            $cartas = $this->cartaModel->obtenerCartasPorAtributoYNombre($atributo_id, $nombre);

            if (empty($cartas)) {
                return ResponseUtil::crearRespuesta($res, ['mensaje' => 'No existen cartas con esos argumentos.']);
            }

            return ResponseUtil::crearRespuesta($res, $cartas);

        } catch (\Exception $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => $e->getMessage()], 500);
        }
    }
}

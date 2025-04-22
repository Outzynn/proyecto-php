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
        $params = $req->getQueryParams(); // Obtener los parámetros de la query string
        $atributo_id = $params['atributo'] ?? null;
        $nombre = $params['nombre'] ?? null;

        if (!$atributo_id || !$nombre) {
            return ResponseUtil::crearRespuesta($res, ["error" => "Faltan parámetros: atributo y nombre son requeridos."], 400);
        }

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

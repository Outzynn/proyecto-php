<?php

namespace App\Utils;

use Psr\Http\Message\ResponseInterface as Response;

class ResponseUtil{

    public static function crearRespuesta(Response $res, $data, $status = 200) {
        $res->getBody()->write(json_encode($data));
        return $res->withStatus($status)->withHeader('Content-Type', 'application/json');
    }
}
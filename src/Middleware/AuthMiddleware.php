<?php
namespace App\Middleware;

use Slim\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use App\Utils\DataBase;
use App\Utils\ResponseUtil;


use PDO;

class AuthMiddleware {
    private const SECRET_KEY = 'clave_para_token';
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getInstance(); //conecto DB
    }

    public function __invoke(Request $request, Handler $handler): Response {
        $token = $request->getHeaderLine('Authorization'); //Obtiene el token del encabezado de la solicitud, "Header".

        // Verificar si el encabezado Authorization está presente
        if (empty($token)) {
            return ResponseUtil::crearRespuesta(new Response(), ["error" => "Token de autenticación no proporcionado"],401);
        }

        // Extraer el token eliminando "Bearer "
        $token = str_replace('Bearer ', '', $token); // En la solicitud se envia "Bearer <TOKEN>", ese Bearer no nos sirve

        try {
            // Decodificar el token con la clave y el algoritmo correctos
            $decoded = JWT::decode($token, new Key(self::SECRET_KEY, 'HS256'));
            $usuarioId = $decoded->sub; // Obtener el ID del usuario del token

            // Verificar si el token es válido en la base de datos
            //Verifico si existe un usuario con el ID que tiene el token y si ambos concuerdan.
            $stmt = $this->pdo->prepare("SELECT id FROM usuario WHERE id = :id AND token = :token AND vencimiento_token > NOW()");
            $stmt->execute([':id' => $usuarioId, ':token' => $token]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);


            if (!$usuario) { 
                return ResponseUtil::crearRespuesta(new Response(), ["error" => "Token inválido o usuario no encontrado"],401);
            }

            // Inyectar el ID del usuario en la request para que esté disponible en los controladores
            $request = $request->withAttribute('usuarioId', $usuarioId);
            return $handler->handle($request);

        } catch (\Exception $e) {
            return ResponseUtil::crearRespuesta(new Response(), ["error" => "Error al validar el token: " . $e->getMessage()],401);
        }
    }

}
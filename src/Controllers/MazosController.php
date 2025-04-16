<?php
namespace App\Controllers;

use App\Utils\ResponseUtil;
use App\Utils\Database;
use PDO;

class MazosController{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance();
    }

    public function crearMazo($req, $res)
    {
        $data = $req->getParsedBody();
        $ids = $data['ids'];
        $nombre = $data['nombre'];
        $usuario = $req->getAttribute('usuarioId');

        // Validar un máximo de 5 ids de cartas y que existan
        if (!$this->cumple($ids, $this->pdo)) {
            return ResponseUtil::crearRespuesta($res, ['error' => 'La cantidad de cartas es mayor a 5 o los IDs no existen.'], 400);
        }

        if ($this->maxMazos($usuario, $this->pdo) >= 3) {
            return ResponseUtil::crearRespuesta($res, ['error' => 'Máxima cantidad de mazos creados alcanzada.'], 400);
        }

        try {
            // Creación del mazo
            $sql = "INSERT INTO mazo (usuario_id, nombre) VALUES (:usuario_id, :nombre)";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':usuario_id' => $usuario,
                ':nombre' => $nombre
            ]);

            $id_mazo = $this->pdo->lastInsertId();

            // Crear registros de las cartas en mazo_cartas.
            $values = [];
            foreach ($ids as $id) {
                $values[] = "($id_mazo, $id, 'en_mazo')";
            }
            $values_str = implode(', ', $values);

            $sql = "INSERT INTO mazo_carta (mazo_id, carta_id, estado) VALUES $values_str";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute(); 

            return ResponseUtil::crearRespuesta($res, ['mensaje' => "MazoID: $id_mazo, Nombre: $nombre"]);

        } catch (\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al insertar: " . $e->getMessage()], 500);
        }
    }

    // Método para contar los mazos del usuario
    private function maxMazos($usuario, $pdo)
    {
        $sql = "SELECT COUNT(*) FROM mazo WHERE usuario_id = :usuario_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':usuario_id' => $usuario]);
        $cantidad = $stmt->fetchColumn();

        return $cantidad;
    }

    // Método para verificar que los IDs sean válidos
    private function cumple($array, $pdo)
    {
        if (count($array) > 5) {
            return false;
        }

        $ids_unicos = array_unique($array);

        // Verificar si hubo repetidos
        if (count($array) !== count($ids_unicos)) {
            return false;
        }

        $placeholders = implode(',', array_fill(0, count($ids_unicos), '?'));
        $sql = "SELECT id FROM carta WHERE id IN ($placeholders)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($ids_unicos);
        $resultados = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (count($resultados) !== count($ids_unicos)) {
            return false;
        } else {
            return true;
        }
    }

    public function borrarMazo($req, $res, $args)
    {
        $usuarioID = $req->getAttribute('usuarioId');
        $nombre_mazo = $args['mazo'];
    
        // Buscar el ID del mazo asociado al usuario y al nombre del mazo
        $idDelMazo = $this->encontrarID($usuarioID, $nombre_mazo, $this->pdo);
    
        // Si no se encuentra el mazo o el usuario no tiene permisos, devolver error
        if (!$idDelMazo) {
            return ResponseUtil::crearRespuesta($res, ["error" => "El mazo no existe o no tienes permisos para borrarlo."], 400);
        }
    
        // Verificar si el mazo está en uso en alguna partida
        $sql = "SELECT COUNT(*) FROM partida WHERE mazo_id = :mazo_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([ ':mazo_id' => $idDelMazo ]);
        $enUso = $stmt->fetchColumn();
    
        // Si el mazo está en uso, no se puede borrar
        if ($enUso > 0) {
            return ResponseUtil::crearRespuesta($res, ["error" => "El mazo está en uso, no se puede borrar."], 400);
        }
    
        // Si no está en uso, proceder con el borrado
        return $this->borrar($idDelMazo, $this->pdo, $res);
    }
    
    private function borrar($idDelMazo, $pdo, $res)
    {
        try {
            // Intentar eliminar el mazo de la base de datos
            $sql = "DELETE FROM mazo WHERE id = :mazo_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([ 'mazo_id' => $idDelMazo ]);
        
            // Verificar si se eliminó alguna fila
            if ($stmt->rowCount() > 0) {
                return ResponseUtil::crearRespuesta($res, ["mensaje" => "El mazo fue eliminado con éxito."]);
            } else {
                // Si no se eliminó ninguna fila, devolver mensaje de error
                return ResponseUtil::crearRespuesta($res, ["error" => "No se pudo eliminar el mazo, puede que ya haya sido borrado."], 400);
            }
        } catch (\PDOException $e) {
            // Capturar errores en la ejecución y devolver respuesta con código 500
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al eliminar: " . $e->getMessage()], 500);
        }
    }
    
    private function encontrarID($usuario, $mazo, $pdo)
    {
        // Buscar el ID del mazo según el usuario y el nombre
        $sql = "SELECT id FROM mazo WHERE usuario_id = :usuario_id AND nombre = :nombre";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([ 'usuario_id' => $usuario, 'nombre' => $mazo ]);
        $idDelMazo = $stmt->fetchColumn();
    
        // Si se encuentra el mazo, devolver el ID, de lo contrario, devolver false
        return $idDelMazo ?: false;
    }

    public function listadoMazos($req,$res,$args){
        $usuario = $args['usuario'];
        $usuario_auth = $req->getAttribute('usuarioId');

        try{
            $sql = "SELECT nombre FROM usuario WHERE id = :id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                ':id' => $usuario_auth
            ]);
            $nombre = $stmt->fetchColumn();

            if ($nombre != $usuario){
                return ResponseUtil::crearRespuesta($res, ["Error: " => "No tienes permiso para obtener los mazos de este usuario",401]);
            }
        }catch(\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al obtener el usuario de la DB: " . $e->getMessage()], 500);
        }
        try{
           $sql = "SELECT id,nombre FROM mazo WHERE usuario_id = :usuarioId";
           $stmt = $this->pdo->prepare($sql);
           $stmt->execute([
              ':usuarioId' => $usuario_auth
           ]);
           $mazos = $stmt->fetchAll(PDO::FETCH_ASSOC);

           return ResponseUtil::crearRespuesta($res,$mazos);

        }catch(\PDOException $e) {
            return ResponseUtil::crearRespuesta($res, ['error' => "Error al traer los mazos de la DB: " . $e->getMessage()], 500);
        }
    }    
}

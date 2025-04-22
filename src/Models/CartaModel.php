<?php
namespace App\Models;

use App\Utils\DataBase;
use PDO;

class CartaModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getInstance(); // Obtener la instancia de la conexión PDO
    }

    public function obtenerCartasPorAtributoYNombre($atributo_id, $nombre) {
        try {
            // Consulta con JOIN para obtener el nombre del atributo también
            $sql = "SELECT c.id, c.nombre, c.ataque, c.ataque_nombre, a.nombre AS atributo_nombre
                    FROM carta c
                    INNER JOIN atributo a ON c.atributo_id = a.id
                    WHERE c.atributo_id = :atributo_id AND c.nombre LIKE :nombre";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([
                'atributo_id' => $atributo_id,
                'nombre' => "%" . $nombre . "%"
            ]);
            
            // Recuperar los resultados
            $cartas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Verificar si se encontraron cartas
            if (empty($cartas)) {
                return []; // Si no se encuentran cartas, devolver un array vacío
            }

            // Agregar el atributo_nombre a la respuesta junto con las cartas
            return $cartas;

        } catch (\PDOException $e) {
            throw new \Exception("Error al procesar la solicitud: " . $e->getMessage());
        }
    }
}

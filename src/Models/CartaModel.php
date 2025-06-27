<?php
namespace App\Models;

use App\Utils\DataBase;
use PDO;

class CartaModel {
    private $pdo;

    public function __construct() {
        $this->pdo = DataBase::getInstance();
    }

    public function buscarCartas(?string $atributo = null, ?string $nombre = null): array
    {
        $sql = "SELECT carta.id, carta.nombre, atributo.nombre AS atributo, carta.ataque 
                FROM carta 
                INNER JOIN atributo ON carta.atributo_id = atributo.id
                WHERE 1=1";
        $params = [];
    
        if ($atributo !== null && $atributo!="") {
            $sql .= " AND atributo.nombre = :atributo";
            $params[':atributo'] = $atributo;
        }
    
        if ($nombre !== null && $nombre != "") {
            $sql .= " AND carta.nombre LIKE :nombre";
            $params[':nombre'] = '%' . $nombre . '%';
        }
    
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
    
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

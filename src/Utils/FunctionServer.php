<?php
namespace App\Utils;
use PDO;
use App\Utils\Database;

class FunctionServer {

    public static function jugadaServidor(PDO $pdo): ?int { //  ?int hace que puedas devolver un null o un entero.

        $sql = "SELECT carta_id FROM mazo_carta WHERE mazo_id = 1 AND estado = 'en_mazo' ORDER BY RAND() LIMIT 1"; //Selecciona una carta de las disponibles, ORDER BY RAND() ordena aleatoriamente las cartas y LIMIT 1 te trae 1 sola. Esto es para traer una carta random de las disponibles.
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $id= $stmt->fetchColumn();

        if (!$id) {
            return null; //Devuelve null si no encontro cartas disponibles.
        }

        $sql = "UPDATE mazo_carta SET estado = 'descartado' WHERE carta_id = :id "; //Udatea el estado de la carta jugada a "descartado".
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return (int) $id; //Devuelve el id de la carta jugada.
    }
}

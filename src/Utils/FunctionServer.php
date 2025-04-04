<?php
namespace App\Utils;
use PDO;
use App\Utils\Database;

class FunctionServer {

    public static function jugadaServidor(PDO $pdo): ?int {

        $sql = "SELECT carta_id FROM mazo_carta WHERE mazo_id = 1 AND estado = 'en_mazo' ORDER BY RAND() LIMIT 1";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        $id= $stmt->fetchColumn();

        if (!$id) {
            return null;
        }

        $sql = "UPDATE mazo_carta SET estado = 'descartado' WHERE carta_id = :id ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([':id' => $id]);

        return (int) $id;
    }
}

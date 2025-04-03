<?php
namespace App\Utils;

use PDO;

class Database {
    private static $instance = null;
    private $pdo;

    private function __construct() {
        $host = 'localhost';
        $dbname = 'proyecto-php'; //Nombre de como tengas la base de datos vos
        $username = 'root';
        $password = '';

        try {
            $this->pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $username, $password); //usamos PDO para mas seguridad y reutilizacion de codigo. EJ. inyecciones SQL.
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (\PDOException $e) {
            die("Error en la conexión a la base de datos: " . $e->getMessage());
        }
    }

    public static function getInstance() { //static para poder llamarla sin crear una instancia, esta funcion verifica si he creado anteriormente una instancia de conexion, sino la crea y la devuelve. 
    // Esto es para no tener 20 conecciones a la DB distintas.
        if (self::$instance === null) {
            self::$instance = new Database(); 
        }
        return self::$instance->pdo;
    }
}

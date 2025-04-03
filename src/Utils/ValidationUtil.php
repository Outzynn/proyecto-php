<?php
namespace App\Utils;

use PDO;

// Aca van las funciones que validan datos.

class ValidationUtil {

    public static function validarClave($clave) {
        $regex = '/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\'":\\|,.<>\/?]).{8,}$/';
        if (preg_match($regex, $clave)) {
            return true;
        } else {
            return false;
        }
    }
    
    public static function validarUsuario($user){ //Funcion que vamos a usar para validar el formato del campo usuario
        if ((strlen($user) < 6 || strlen($user) > 20) || !ctype_alnum($user)){
            return false;
        } else {
            return true;
        }
    }

}
# SeminarioPHP
Nombre del Proyecto: Seminario PHP - React y API rest

Descripción:
Una API RESTful para gestionar usuarios y partidas de un juego de cartas.

Requisitos del Servidor:

* **Servidor Web:** Apache o Nginx recomendado.
* **PHP:** Versión 7.4 o superior (se recomienda PHP 8.x para mejor rendimiento y características).

Dependencias de PHP (Gestionadas con Composer):

Este proyecto utiliza Composer para gestionar sus dependencias. Asegúrate de tener Composer instalado en tu sistema.

Para instalar las dependencias:

1.  Navega al directorio raíz del proyecto en tu terminal.
2.  Ejecuta el siguiente comando:

    composer install

    Esto instalará todas las librerías listadas en el archivo `composer.json`, incluyendo:
    * Slim Framework (para la estructura de la aplicación y el enrutamiento).
    * firebase/php-jwt (para la generación y verificación de JSON Web Tokens).

Configuración:

1.  **Configura las variables de entorno:**
    Abre el archivo de configuración y establece los valores necesarios para tu entorno:
    * **Base de Datos:** Host, nombre de la base de datos, usuario, contraseña.
    * **Clave Secreta JWT:** Una clave segura utilizada para firmar los tokens JWT.

Instalación (Pasos Generales):

1.  Clona el repositorio del proyecto en tu servidor local o de desarrollo.
2.  Asegúrate de que se cumplan los requisitos del servidor mencionados anteriormente.
3.  Navega al directorio del proyecto en la terminal.
4.  Instala las dependencias de PHP utilizando Composer (`composer install`).
5.  Configura las variables de entorno según sea necesario.
6.  Configura tu servidor web (Apache o Nginx) para que apunte al directorio `public` de tu proyecto como la raíz del documento.
7.  Asegúrate de que la base de datos esté configurada y las credenciales sean correctas en tu archivo de configuración.
8.  Ejecuta cualquier migración de base de datos o script de inicialización necesario.

**Endpoints de la API:**

**Autenticación:**
POST /registro
POST /login
Se deben pasar por el BODY de JSON el nombre,usuario y password.

**Usuarios (Requiere Token JWT en la cabecera `Authorization`):**
PUT /usuarios/{usuario}: Editar al usuario logueado (solo nombre y password). Requiere en el BODY nombre y password para actualizarlas.
GET /usuarios/{usuario}: Obtener información del usuario logueado.

**Juego (Requiere Token JWT en la cabecera `Authorization`. Valida que el mazo pertenece al usuario autenticado por el token):**
POST /partidas (recibe id de mazo en el cuerpo)
POST /jugadas (recibe carta jugada e id de partida en el cuerpo)
GET /usuarios/{usuario}/partidas/{partida}/cartas (opcional)

**Mazos (Requiere Token JWT en la cabecera `Authorization`):**
POST /mazos (recibe ids de cartas y nombre en el cuerpo)
DELETE /mazos/{mazo} (se le debe pasar el ID del mazo en el argumento)
GET /usuarios/{usuario}/mazos (se le debe pasar el ID del usuario en el argumento)
PUT /mazos/{mazo} (recibe nombre en el cuerpo) (se le debe pasar el ID del mazo en el argumento)

**Cartas:**
GET /cartas?atributo={atributo}&nombre={nombre}

**Estadísticas (No Requiere Autenticación):**
GET /estadisticas


Notas Adicionales:

* Este proyecto sigue las convenciones de codificación PSR estándar de PHP.
* Se recomienda configurar un entorno de desarrollo separado de tu entorno de producción.
* Asegúrate de que el directorio `vendor` no esté expuesto directamente por tu servidor web.

Contacto:
Alfonso Marianela, San Pedro Agustin.
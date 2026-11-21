<?php
declare(strict_types=1);

define('BASE_PATH', dirname(__DIR__));

date_default_timezone_set('America/Mexico_City');
ini_set('display_errors', '0');   // los errores van al log, no al JSON
error_reporting(E_ALL);

// Configuracion local opcional: no se versiona y permite definir DB_* y MP_ACCESS_TOKEN.
$archivoLocal = BASE_PATH . '/config/local.php';
if (is_file($archivoLocal)) {
    $variablesLocales = require $archivoLocal;
    if (is_array($variablesLocales)) {
        foreach ($variablesLocales as $nombre => $valor) {
            if (is_string($nombre) && is_scalar($valor)) {
                putenv($nombre . '=' . (string)$valor);
                $_ENV[$nombre] = (string)$valor;
            }
        }
    }
}

// Clase de conexion: todo el acceso a MySQL se concentra en modelos.
require_once BASE_PATH . '/config/database.php';

/** Escapa texto para imprimirlo en HTML (evita XSS en las vistas). */
function e(mixed $v): string
{
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

/** Helpers Unicode con fallback para instalaciones PHP donde mbstring no está habilitado. */
function poly_mb_strlen(string $v): int
{
    return function_exists('mb_strlen') ? mb_strlen($v, 'UTF-8') : strlen($v);
}

function poly_mb_substr(string $v, int $inicio, ?int $longitud = null): string
{
    if (function_exists('mb_substr')) return mb_substr($v, $inicio, $longitud, 'UTF-8');
    return $longitud === null ? substr($v, $inicio) : substr($v, $inicio, $longitud);
}

function poly_mb_strtolower(string $v): string
{
    return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
}

// Autoloader: App\Core\Router -> app/Core/Router.php
spl_autoload_register(function (string $clase): void {
    $prefijo = 'App\\';
    if (strncmp($clase, $prefijo, strlen($prefijo)) !== 0) {
        return;
    }
    $ruta = BASE_PATH . '/app/' . str_replace('\\', '/', substr($clase, strlen($prefijo))) . '.php';
    if (is_file($ruta)) {
        require $ruta;
    }
});

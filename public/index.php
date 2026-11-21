<?php
declare(strict_types=1);

/**
 * FRONT CONTROLLER: unico punto de entrada. Toda peticion pasa por aqui.
 */

// Solo para `php -S` (servidor de desarrollo): que entregue tal cual los archivos reales
// (css, imagenes) en lugar de pasarlos por el router. Apache ya lo resuelve con .htaccess.
if (PHP_SAPI === 'cli-server') {
    $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $archivo = realpath(__DIR__ . $ruta);
    if ($ruta !== '/' && $archivo !== false && is_file($archivo) && str_starts_with($archivo, realpath(__DIR__))
        && basename($archivo) !== 'index.php') {
        return false;
    }
}

require_once __DIR__ . '/../app/bootstrap.php';

use App\Core\Request;
use App\Core\Router;
use App\Core\Response;
use App\Core\HttpException;

header_remove('X-Powered-By');

try {
    // Ensamblaje DI: AcademiaController solo conoce el contrato del repositorio.
    // Puede sustituirse MySQL por una implementación falsa en pruebas.
    $contenedor = new \App\Core\Container();
    $contenedor->registrar(\App\Controllers\AcademiaController::class,
        static fn() => new \App\Controllers\AcademiaController(new \App\Models\Academia()));
    $router = new Router($contenedor);
    require __DIR__ . '/../app/routes.php';   // define $router->get(...), etc.

    $respuesta = $router->dispatch(Request::capturar());
} catch (HttpException $e) {
    $respuesta = Response::error($e->getStatus(), $e->getMessage(), $e->getDetalles(), $e->getHeaders());
} catch (\Throwable $e) {
    // Nunca se filtra el detalle interno al cliente; solo al log del servidor.
    error_log('[Polycraft] ' . $e::class . ': ' . $e->getMessage() . ' en ' . $e->getFile() . ':' . $e->getLine());
    $respuesta = Response::error(500, 'Error interno del servidor');
}

$respuesta->enviar();

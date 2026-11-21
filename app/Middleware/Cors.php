<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

/**
 * CORS explicito. Se ejecuta PRIMERO (middleware global).
 *  - Sin header Origin (Postman, curl, servidor a servidor): pasa sin cambios.
 *  - Origin del propio servidor o incluido en config/cors.php: se autoriza.
 *  - Cualquier otro Origin: 403, la accion del controlador nunca se ejecuta.
 *  - Preflight (OPTIONS + Origin): se responde aqui mismo con 204.
 * Como la sesion usa cookie, se envia Allow-Credentials y NUNCA "*".
 */
class Cors implements Middleware
{
    private array $config;

    public function __construct()
    {
        $this->config = require BASE_PATH . '/config/cors.php';
    }

    public function handle(Request $req, callable $siguiente): Response
    {
        $origen = $req->header('origin');
        if ($origen === null) {
            return $siguiente($req);
        }

        $autorizado = $origen === $req->origenPropio()
            || in_array($origen, $this->config['origenes'], true);

        if (!$autorizado) {
            throw new HttpException(403, 'Origen no autorizado');
        }

        $respuesta = ($req->metodo() === 'OPTIONS' && $req->header('access-control-request-method') !== null)
            ? Response::sinContenido()
                ->conHeader('Access-Control-Allow-Methods', $this->config['metodos'])
                ->conHeader('Access-Control-Allow-Headers', $this->config['headers'])
                ->conHeader('Access-Control-Max-Age', (string)$this->config['max_age'])
            : $siguiente($req);

        return $respuesta
            ->conHeader('Access-Control-Allow-Origin', $origen)
            ->conHeader('Access-Control-Allow-Credentials', 'true')
            ->conHeader('Vary', 'Origin');
    }
}

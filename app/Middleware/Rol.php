<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;

/**
 * Permite la accion solo a ciertos roles. Va SIEMPRE despues de Autenticado:
 *   [Autenticado::class, Rol::permitir('administrador')]
 */
class Rol implements Middleware
{
    private array $roles;

    public function __construct(string ...$roles)
    {
        $this->roles = $roles;
    }

    public static function permitir(string ...$roles): string
    {
        return self::class . ':' . implode(',', $roles);
    }

    public function handle(Request $req, callable $siguiente): Response
    {
        $usuario = $req->atributo('usuario');
        if ($usuario === null) {
            // Mal configurado (falta Autenticado antes): se falla cerrado, nunca abierto.
            throw new \LogicException('Rol requiere que Autenticado se ejecute antes');
        }
        if (!in_array($usuario['rol'], $this->roles, true)) {
            throw new HttpException(403, 'No tienes permiso para realizar esta accion');
        }
        return $siguiente($req);
    }
}

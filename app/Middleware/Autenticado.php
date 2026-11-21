<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Core\HttpException;
use App\Core\Middleware;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Usuario;

/**
 * Exige una sesion valida ANTES de ejecutar la accion del controlador.
 * Revalida contra la BD en cada peticion: si el administrador deshabilita
 * a un usuario con sesion abierta, pierde el acceso de inmediato.
 * El control por rol lo hace el middleware Rol, que va despues de este.
 */
class Autenticado implements Middleware
{
    public function handle(Request $req, callable $siguiente): Response
    {
        $id = Session::usuarioId();
        if ($id === null) {
            throw new HttpException(401, 'Debes iniciar sesion');
        }

        $usuario = (new Usuario())->porId($id);
        if ($usuario === null || !(int)$usuario['activo']) {
            Session::cerrar();
            throw new HttpException(401, 'Sesion no valida');
        }

        $req->setAtributo('usuario', $usuario);
        return $siguiente($req);
    }
}

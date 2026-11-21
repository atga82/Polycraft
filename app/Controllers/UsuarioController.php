<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Usuario;

/** Gestion de usuarios: SOLO administrador (lo garantiza el middleware Rol en las rutas). */
class UsuarioController extends Controller
{
    /** GET /api/usuarios?rol=estudiante|instructor|administrador&pagina=1&limite=50 */
    public function listar(Request $req, array $params): Response
    {
        $rol = $req->query('rol');
        if ($rol !== null && !in_array($rol, ['estudiante', 'instructor', 'administrador'], true)) {
            throw new HttpException(422, 'Parametro invalido', ['rol' => 'Debe ser estudiante, instructor o administrador']);
        }
        $limite = min(100, max(1, (int)$req->query('limite', 50)));
        $pagina = max(1, (int)$req->query('pagina', 1));

        $datos = (new Usuario())->listar($rol, $limite, ($pagina - 1) * $limite);
        return Response::json($datos);
    }

    /** PATCH /api/usuarios/{id}/estado   cuerpo: {"activo": true|false} */
    public function estado(Request $req, array $params): Response
    {
        $d = $req->json();
        if (!isset($d['activo']) || !is_bool($d['activo'])) {
            throw new HttpException(422, 'Datos invalidos', ['activo' => 'Debe ser true o false']);
        }

        $usuarios = new Usuario();
        $id = ctype_digit($params['id']) ? (int)$params['id'] : 0;
        $objetivo = $id > 0 ? $usuarios->porId($id) : null;
        if ($objetivo === null) {
            throw new HttpException(404, 'Usuario no encontrado');
        }
        if ($id === (int)$req->atributo('usuario')['id']) {
            throw new HttpException(409, 'No puedes cambiar el estado de tu propia cuenta');
        }

        $usuarios->cambiarEstado($id, $d['activo']);
        return Response::json(Usuario::publicoAdmin($usuarios->porId($id)));
    }
}

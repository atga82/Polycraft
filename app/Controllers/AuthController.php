<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Usuario;

class AuthController extends Controller
{
    /** POST /api/auth/registro */
    public function registro(Request $req, array $params): Response
    {
        $d = $req->json();
        $this->exigir($d, ['rol', 'nombre_completo', 'email', 'genero', 'fecha_nacimiento', 'password']);

        $errores = array_filter([
            'nombre_completo'  => Validator::nombre($d['nombre_completo']),
            'email'            => Validator::email($d['email']),
            'genero'           => Validator::genero($d['genero']),
            'fecha_nacimiento' => Validator::fechaNacimiento($d['fecha_nacimiento']),
            'password'         => Validator::password($d['password']),
            // El administrador NO se puede crear por registro publico.
            'rol'              => in_array($d['rol'], ['estudiante', 'instructor'], true)
                                    ? null : 'Debe ser estudiante o instructor',
        ]);
        if ($errores) {
            throw new HttpException(422, 'Datos invalidos', $errores);
        }

        $usuarios = new Usuario();
        $email = poly_mb_strtolower(trim($d['email']));
        if ($usuarios->emailEnUso($email)) {
            throw new HttpException(409, 'Ya existe una cuenta con ese email');
        }

        try {
            $id = $usuarios->crear([
                'rol_id'           => $usuarios->rolId($d['rol']),
                'nombre_completo'  => trim($d['nombre_completo']),
                'genero'           => $d['genero'],
                'fecha_nacimiento' => $d['fecha_nacimiento'],
                'email'            => $email,
                'password_hash'    => password_hash($d['password'], PASSWORD_DEFAULT),
            ]);
        } catch (\DomainException) {               // otro registro igual llego primero
            throw new HttpException(409, 'Ya existe una cuenta con ese email');
        }

        return Response::creado(Usuario::publico($usuarios->porId($id)), '/api/perfil');
    }

    /** POST /api/auth/login  — bloquea la cuenta al 3er intento fallido consecutivo. */
    public function login(Request $req, array $params): Response
    {
        $d = $req->json();
        $this->exigir($d, ['email', 'password']);
        if (!is_string($d['email']) || !is_string($d['password'])) {
            throw new HttpException(422, 'Datos invalidos', ['email' => 'Debe ser texto', 'password' => 'Debe ser texto']);
        }

        $usuarios = new Usuario();
        $u = $usuarios->porEmail(poly_mb_strtolower(trim($d['email'])));

        if ($u === null) {
            password_verify($d['password'], Usuario::HASH_FALSO);
            throw new HttpException(401, 'Email o contrasena incorrectos');
        }

        if (!(int)$u['activo']) {
            throw new HttpException(403, 'Cuenta deshabilitada. Contacta al administrador para reactivarla');
        }

        if (!password_verify($d['password'], $u['password_hash'])) {
            $estado = $usuarios->registrarIntentoFallido((int)$u['id']);
            if (!$estado['activo']) {
                throw new HttpException(403, 'Cuenta deshabilitada por 3 intentos fallidos. Contacta al administrador');
            }
            throw new HttpException(401, 'Email o contrasena incorrectos', [
                'intentos_restantes' => Usuario::MAX_INTENTOS - $estado['intentos'],
            ]);
        }

        if ((int)$u['intentos_fallidos'] > 0) {
            $usuarios->reiniciarIntentos((int)$u['id']);   // el conteo es de intentos CONSECUTIVOS
        }
        Session::abrir((int)$u['id']);

        return Response::json(Usuario::publico($u));
    }

    /** POST /api/auth/logout */
    public function logout(Request $req, array $params): Response
    {
        Session::cerrar();
        return Response::sinContenido();
    }
}

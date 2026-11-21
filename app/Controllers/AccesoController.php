<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Avatar;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Validator;
use App\Core\View;
use App\Models\Usuario;

/**
 * Registro, inicio y cierre de sesion con formularios HTML clasicos
 * (POST normal + redireccion). Usa los mismos modelos y validaciones que la API.
 */
class AccesoController extends Controller
{
    private const MSG_CSRF = 'El formulario expiro. Intenta de nuevo.';

    // ------------------------------------------------------------ LOGIN

    /** GET /login */
    public function formLogin(Request $req, array $p): Response
    {
        if (Session::usuarioId() !== null) {
            return Response::redirigir($req->base() . '/');
        }
        return Response::html(View::render('auth/Login', $req, [
            'csrf'     => Session::csrf(),
            'error'    => Session::tomarFlash('error'),
            'exito'    => Session::tomarFlash('exito'),
            'anterior' => Session::tomarFlash('anterior', []),
        ]));
    }

    /** POST /login — bloquea la cuenta al 3er intento fallido consecutivo. */
    public function login(Request $req, array $p): Response
    {
        $volver = Response::redirigir($req->base() . '/login');

        if (!Session::csrfValido($req->post('csrf'))) {
            Session::flash('error', self::MSG_CSRF);
            return $volver;
        }

        $email = $req->post('email');
        $pass  = $req->post('password');
        if (!is_string($email) || !is_string($pass) || trim($email) === '' || $pass === '') {
            Session::flash('error', 'Ingresa tu email y tu contraseña.');
            return $volver;
        }
        $email = poly_mb_strtolower(trim($email));
        Session::flash('anterior', ['email' => $email]);

        $usuarios = new Usuario();
        $u = $usuarios->porEmail($email);

        if ($u === null) {
            password_verify($pass, Usuario::HASH_FALSO);    // mismo tiempo que un password incorrecto
            Session::flash('error', 'Email o contraseña incorrectos.');
            return $volver;
        }
        if (!(int)$u['activo']) {
            Session::flash('error', 'Cuenta deshabilitada. Contacta al administrador para reactivarla.');
            return $volver;
        }
        if (!password_verify($pass, $u['password_hash'])) {
            $estado = $usuarios->registrarIntentoFallido((int)$u['id']);
            Session::flash('error', $estado['activo']
                ? 'Email o contraseña incorrectos. Intentos restantes: ' . (Usuario::MAX_INTENTOS - $estado['intentos']) . '.'
                : 'Cuenta deshabilitada por 3 intentos fallidos. Contacta al administrador.');
            return $volver;
        }

        if ((int)$u['intentos_fallidos'] > 0) {
            $usuarios->reiniciarIntentos((int)$u['id']);    // el conteo es de intentos CONSECUTIVOS
        }
        Session::abrir((int)$u['id']);
        return Response::redirigir($req->base() . '/');
    }

    // --------------------------------------------------------- REGISTRO

    /** GET /registro */
    public function formRegistro(Request $req, array $p): Response
    {
        if (Session::usuarioId() !== null) {
            return Response::redirigir($req->base() . '/');
        }
        return Response::html(View::render('auth/Register', $req, [
            'csrf'     => Session::csrf(),
            'error'    => Session::tomarFlash('error'),
            'errores'  => Session::tomarFlash('errores', []),
            'anterior' => Session::tomarFlash('anterior', []),
        ]));
    }

    /** POST /registro */
    public function registro(Request $req, array $p): Response
    {
        $volver = Response::redirigir($req->base() . '/registro');

        if (!Session::csrfValido($req->post('csrf'))) {
            Session::flash('error', self::MSG_CSRF);
            return $volver;
        }

        $d = [];
        foreach (['tipo', 'nombre_completo', 'email', 'genero', 'fecha_nacimiento', 'password'] as $c) {
            $v = $req->post($c, '');
            $d[$c] = is_string($v) ? $v : '';
        }

        $errores = array_filter([
            'tipo'             => in_array($d['tipo'], ['estudiante', 'instructor'], true)
                                    ? null : 'Selecciona estudiante o instructor.',
            'nombre_completo'  => Validator::nombre($d['nombre_completo']),
            'email'            => Validator::email($d['email']),
            'genero'           => Validator::genero($d['genero']),
            'fecha_nacimiento' => Validator::fechaNacimiento($d['fecha_nacimiento']),
            'password'         => Validator::password($d['password']),
        ]);

        $email    = poly_mb_strtolower(trim($d['email']));
        $usuarios = new Usuario();
        if (!isset($errores['email']) && $usuarios->emailEnUso($email)) {
            $errores['email'] = 'Ya existe una cuenta con ese email.';
        }

        // Fotografia: opcional en el servidor (si no llega se usa default.png).
        $foto   = $req->archivo('foto');
        $avatar = 'default.png';
        if (!$errores && $foto !== null && $foto['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $avatar = Avatar::guardar($foto);
            } catch (HttpException $e) {
                $errores['foto'] = $e->getMessage();
            }
        }

        if (!$errores) {
            try {
                $usuarios->crear([
                    'rol_id'           => $usuarios->rolId($d['tipo']),
                    'nombre_completo'  => trim($d['nombre_completo']),
                    'genero'           => $d['genero'],
                    'fecha_nacimiento' => $d['fecha_nacimiento'],
                    'avatar'           => $avatar,
                    'email'            => $email,
                    'password_hash'    => password_hash($d['password'], PASSWORD_DEFAULT),
                ]);
            } catch (\DomainException) {                    // otro registro igual llego primero
                Avatar::borrar($avatar);
                $errores['email'] = 'Ya existe una cuenta con ese email.';
            }
        }

        if ($errores) {
            unset($d['password']);                          // la contraseña nunca se devuelve al formulario
            Session::flash('errores', $errores);
            Session::flash('anterior', $d);
            return $volver;
        }

        Session::flash('exito', 'Cuenta creada. Ya puedes iniciar sesión.');
        return Response::redirigir($req->base() . '/login');
    }

    // ----------------------------------------------------------- LOGOUT

    /** POST /logout */
    public function logout(Request $req, array $p): Response
    {
        if (Session::csrfValido($req->post('csrf'))) {
            Session::cerrar();
        }
        return Response::redirigir($req->base() . '/');
    }
}

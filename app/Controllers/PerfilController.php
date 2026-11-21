<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Avatar;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Core\Validator;
use App\Models\Usuario;

/** Perfil del usuario autenticado (igual para estudiante, instructor y administrador). */
class PerfilController extends Controller
{
    /** GET /api/perfil */
    public function mostrar(Request $req, array $params): Response
    {
        return Response::json(Usuario::publico($req->atributo('usuario')));
    }

    /**
     * PATCH|PUT /api/perfil — actualiza solo los campos que lleguen.
     * Para cambiar la contrasena: password_nueva + password_actual.
     */
    public function actualizar(Request $req, array $params): Response
    {
        $actual = $req->atributo('usuario');
        $d = $req->json();

        $reglas = [
            'nombre_completo'  => [Validator::class, 'nombre'],
            'email'            => [Validator::class, 'email'],
            'genero'           => [Validator::class, 'genero'],
            'fecha_nacimiento' => [Validator::class, 'fechaNacimiento'],
        ];

        $cambios = [];
        $errores = [];
        foreach ($reglas as $campo => $regla) {
            if (!array_key_exists($campo, $d)) continue;
            if ($msg = $regla($d[$campo])) {
                $errores[$campo] = $msg;
            } else {
                $cambios[$campo] = is_string($d[$campo]) ? trim($d[$campo]) : $d[$campo];
            }
        }
        if (isset($cambios['email'])) {
            $cambios['email'] = poly_mb_strtolower($cambios['email']);
        }

        if (array_key_exists('password_nueva', $d)) {
            if ($msg = Validator::password($d['password_nueva'])) {
                $errores['password_nueva'] = $msg;
            }
            if (!isset($d['password_actual']) || !is_string($d['password_actual'])
                || !password_verify($d['password_actual'], $actual['password_hash'])) {
                $errores['password_actual'] = 'La contrasena actual es incorrecta';
            }
            if (!isset($errores['password_nueva']) && !isset($errores['password_actual'])) {
                $cambios['password_hash'] = password_hash($d['password_nueva'], PASSWORD_DEFAULT);
            }
        }

        if ($errores) {
            throw new HttpException(422, 'Datos invalidos', $errores);
        }
        if (!$cambios) {
            throw new HttpException(422, 'No se envio ningun campo para actualizar');
        }

        $usuarios = new Usuario();
        try {
            $usuarios->actualizarPerfil((int)$actual['id'], $cambios);
        } catch (\DomainException) {
            throw new HttpException(409, 'Ya existe una cuenta con ese email');
        }

        return Response::json(Usuario::publico($usuarios->porId((int)$actual['id'])));
    }

    /** POST /api/perfil/avatar — multipart/form-data, campo "avatar" (jpg, png o webp, max 2 MB). */
    public function avatar(Request $req, array $params): Response
    {
        $actual = $req->atributo('usuario');
        $nombre = Avatar::guardar($req->archivo('avatar'));

        $usuarios = new Usuario();
        $usuarios->actualizarPerfil((int)$actual['id'], ['avatar' => $nombre]);
        Avatar::borrar($actual['avatar']);          // no se acumulan avatares viejos

        return Response::json(Usuario::publico($usuarios->porId((int)$actual['id'])));
    }
}

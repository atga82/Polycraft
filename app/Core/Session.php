<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Sesion de PHP con cookie endurecida (HttpOnly + SameSite).
 */
final class Session
{
    private const CLAVE = 'usuario_id';

    public static function iniciar(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_name('POLYCRAFTSESSID');
        session_set_cookie_params([
            'lifetime' => 0,          // muere al cerrar el navegador
            'path'     => '/',
            'secure'   => $https,
            'httponly' => true,       // JS no puede leer la cookie
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function usuarioId(): ?int
    {
        self::iniciar();
        return isset($_SESSION[self::CLAVE]) ? (int)$_SESSION[self::CLAVE] : null;
    }

    /** Mensaje de una sola lectura (sobrevive a UNA redireccion). */
    public static function flash(string $clave, mixed $valor): void
    {
        self::iniciar();
        $_SESSION['_flash'][$clave] = $valor;
    }

    public static function tomarFlash(string $clave, mixed $defecto = null): mixed
    {
        self::iniciar();
        $valor = $_SESSION['_flash'][$clave] ?? $defecto;
        unset($_SESSION['_flash'][$clave]);
        return $valor;
    }

    /** Token anti-CSRF de la sesion (va como campo oculto en cada formulario POST). */
    public static function csrf(): string
    {
        self::iniciar();
        return $_SESSION['_csrf'] ??= bin2hex(random_bytes(32));
    }

    public static function csrfValido(mixed $token): bool
    {
        self::iniciar();
        return is_string($token) && isset($_SESSION['_csrf']) && hash_equals($_SESSION['_csrf'], $token);
    }

    public static function abrir(int $usuarioId): void
    {
        self::iniciar();
        session_regenerate_id(true);   // evita fijacion de sesion
        $_SESSION[self::CLAVE] = $usuarioId;
    }

    public static function cerrar(): void
    {
        self::iniciar();
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', [
                'expires' => time() - 3600, 'path' => $p['path'],
                'secure' => $p['secure'], 'httponly' => true, 'samesite' => 'Lax',
            ]);
        }
        session_destroy();
    }
}

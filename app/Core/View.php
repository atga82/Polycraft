<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Renderiza una vista de app/Views y devuelve el HTML como texto.
 * El nombre de la vista lo fija el controlador (nunca viene del usuario).
 * Dentro de la vista estan disponibles $base (prefijo de URL) y las variables de $datos.
 */
final class View
{
    public static function render(string $vista, Request $req, array $datos = []): string
    {
        $archivo = BASE_PATH . '/app/Views/' . $vista . '.php';
        if (!is_file($archivo)) {
            throw new \RuntimeException("Vista no encontrada: $vista");
        }

        $base = htmlspecialchars($req->base(), ENT_QUOTES, 'UTF-8');
        extract($datos, EXTR_SKIP);

        ob_start();
        try {
            include $archivo;
            return (string)ob_get_clean();
        } catch (\Throwable $e) {
            ob_end_clean();
            throw $e;
        }
    }
}

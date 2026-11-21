<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Base de los controladores. Los controladores NO tocan la BD:
 * reciben la peticion, llaman al modelo y devuelven una Response.
 */
abstract class Controller
{
    /** Campos obligatorios; lanza 422 con el detalle de lo que falta. */
    protected function exigir(array $datos, array $campos): void
    {
        $faltan = [];
        foreach ($campos as $c) {
            if (!isset($datos[$c]) || (is_string($datos[$c]) && trim($datos[$c]) === '')) {
                $faltan[$c] = 'Campo obligatorio';
            }
        }
        if ($faltan) {
            throw new HttpException(422, 'Datos invalidos', $faltan);
        }
    }
}

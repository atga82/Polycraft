<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Reglas de validacion reutilizables. Cada metodo devuelve el mensaje de
 * error, o null si el valor es valido.
 */
final class Validator
{
    public static function nombre(mixed $v): ?string
    {
        if (!is_string($v)) return 'Debe ser texto';
        $n = poly_mb_strlen(trim($v));
        return ($n < 2 || $n > 120) ? 'Debe tener entre 2 y 120 caracteres' : null;
    }

    public static function email(mixed $v): ?string
    {
        if (!is_string($v) || strlen($v) > 150 || !filter_var($v, FILTER_VALIDATE_EMAIL)) {
            return 'Email invalido';
        }
        return null;
    }

    public static function genero(mixed $v): ?string
    {
        return in_array($v, ['masculino', 'femenino', 'otro'], true)
            ? null : 'Debe ser masculino, femenino u otro';
    }

    public static function fechaNacimiento(mixed $v): ?string
    {
        if (!is_string($v)) return 'Formato esperado: YYYY-MM-DD';
        $f = \DateTime::createFromFormat('!Y-m-d', $v);
        if (!$f || $f->format('Y-m-d') !== $v) return 'Formato esperado: YYYY-MM-DD';
        if ($f > new \DateTime('today')) return 'No puede ser una fecha futura';
        if ($f < new \DateTime('1900-01-01')) return 'Fecha fuera de rango';
        return null;
    }

    /** Minimo 8 caracteres, una mayuscula, un numero y un caracter especial. */
    public static function password(mixed $v): ?string
    {
        if (!is_string($v)) return 'Debe ser texto';
        if (strlen($v) > 72) return 'Maximo 72 caracteres';   // limite de bcrypt
        if (poly_mb_strlen($v) < 8) return 'Debe tener al menos 8 caracteres';
        if (!preg_match('/\p{Lu}/u', $v)) return 'Debe incluir al menos una mayuscula';
        if (!preg_match('/\p{Nd}/u', $v)) return 'Debe incluir al menos un numero';
        if (!preg_match('/[^\p{L}\p{N}\s]/u', $v)) return 'Debe incluir al menos un caracter especial';
        return null;
    }
}

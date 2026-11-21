<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Contrato de middleware. Puede cortar la peticion (lanzando HttpException
 * o devolviendo su propia Response) o dejarla pasar con $siguiente($req).
 */
interface Middleware
{
    public function handle(Request $req, callable $siguiente): Response;
}

<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Se lanza desde cualquier capa para cortar la peticion con un codigo HTTP.
 * Ej: throw new HttpException(404, 'Curso no encontrado');
 */
class HttpException extends \RuntimeException
{
    public function __construct(
        private int $status,
        string $mensaje,
        private array $detalles = [],
        private array $headers = []
    ) {
        parent::__construct($mensaje, $status);
    }

    public function getStatus(): int   { return $this->status; }
    public function getDetalles(): array { return $this->detalles; }
    public function getHeaders(): array  { return $this->headers; }
}

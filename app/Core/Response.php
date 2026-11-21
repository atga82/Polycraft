<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Formato unico de respuesta:
 *   exito: { "ok": true,  "data": ... }
 *   error: { "ok": false, "error": { "codigo": 422, "mensaje": "...", "detalles": {...} } }
 */
class Response
{
    public function __construct(
        private int $status = 200,
        private mixed $cuerpo = null,
        private array $headers = [],
        private bool $esHtml = false
    ) {}

    public static function json(mixed $data, int $status = 200, array $headers = []): self
    {
        return new self($status, ['ok' => true, 'data' => $data], $headers);
    }

    public static function html(string $html, int $status = 200): self
    {
        return new self($status, $html, [], true);
    }

    /** Redireccion clasica (303: despues de un POST el navegador vuelve con GET). */
    public static function redirigir(string $url): self
    {
        return new self(303, null, ['Location' => $url]);
    }

    public static function creado(mixed $data, string $location = ''): self
    {
        return self::json($data, 201, $location !== '' ? ['Location' => $location] : []);
    }

    public static function sinContenido(): self
    {
        return new self(204, null);
    }

    public static function error(int $status, string $mensaje, array $detalles = [], array $headers = []): self
    {
        $error = ['codigo' => $status, 'mensaje' => $mensaje];
        if ($detalles) {
            $error['detalles'] = $detalles;
        }
        return new self($status, ['ok' => false, 'error' => $error], $headers);
    }

    public function conHeader(string $nombre, string $valor): self
    {
        $this->headers[$nombre] = $valor;
        return $this;
    }

    public function enviar(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $nombre => $valor) {
            header("$nombre: $valor");
        }
        if ($this->cuerpo === null) {
            return;
        }
        if ($this->esHtml) {
            header('Content-Type: text/html; charset=utf-8');
            echo $this->cuerpo;
            return;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->cuerpo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }
}

<?php
declare(strict_types=1);

namespace App\Core;

class Request
{
    private function __construct(
        private string $metodo,
        private string $ruta,
        private array $query,
        private array $headers,
        private string $cuerpoCrudo,
        private array $archivos = [],
        private array $atributos = [],
        private string $base = '',
        private array $post = []
    ) {}

    public static function capturar(): self
    {
        $uri  = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

        // Si el proyecto vive en una subcarpeta (XAMPP) se quita ese prefijo. Pueden llegar dos formas:
        //   /Polycraft-main/public/api/health  (entrando directo por /public)
        //   /Polycraft-main/api/health         (el .htaccess de la raiz reescribe a /public)
        // Se prueba primero el prefijo mas largo.
        $publico = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '')), '/');   // /Polycraft-main/public
        $raiz    = rtrim(str_replace('\\', '/', dirname($publico)), '/');                         // /Polycraft-main
        $base = '';
        foreach ([$publico, $raiz] as $prefijo) {
            // El limite exige "/" o fin de ruta, para no confundir /Polycraft-main con /Polycraft-mainX
            if ($prefijo !== '' && ($uri === $prefijo || str_starts_with($uri, $prefijo . '/'))) {
                $uri = substr($uri, strlen($prefijo));
                $base = $prefijo;   // lo usan las vistas para armar enlaces
                break;
            }
        }
        $uri = '/' . trim($uri, '/');

        $headers = [];
        foreach ($_SERVER as $k => $v) {
            if (str_starts_with($k, 'HTTP_')) {
                $headers[strtolower(str_replace('_', '-', substr($k, 5)))] = $v;
            }
        }
        if (isset($_SERVER['CONTENT_TYPE'])) {
            $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
        }

        return new self(
            strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'),
            $uri,
            $_GET,
            $headers,
            file_get_contents('php://input') ?: '',
            $_FILES,
            [],
            $base,
            $_POST
        );
    }

    /** Prefijo de URL por el que se entro (ej. "/Polycraft-main"), para enlaces y recursos. */
    public function base(): string { return $this->base; }

    public function metodo(): string { return $this->metodo; }
    public function ruta(): string   { return $this->ruta; }

    public function query(string $clave, mixed $defecto = null): mixed
    {
        return $this->query[$clave] ?? $defecto;
    }

    /** Origen del propio servidor (esquema://host[:puerto]), para reconocer peticiones del mismo origen. */
    public function origenPropio(): string
    {
        $https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        return ($https ? 'https' : 'http') . '://' . ($this->header('host') ?? '');
    }

    public function header(string $nombre): ?string
    {
        return $this->headers[strtolower($nombre)] ?? null;
    }

    /** Campo de un formulario clasico (application/x-www-form-urlencoded o multipart). */
    public function post(string $clave, mixed $defecto = null): mixed
    {
        return $this->post[$clave] ?? $defecto;
    }

    /** Archivo subido (multipart/form-data) o null. */
    public function archivo(string $campo): ?array
    {
        return $this->archivos[$campo] ?? null;
    }

    /** Datos que un middleware deja para el controlador (ej. el usuario autenticado). */
    public function setAtributo(string $clave, mixed $valor): void
    {
        $this->atributos[$clave] = $valor;
    }

    public function atributo(string $clave): mixed
    {
        return $this->atributos[$clave] ?? null;
    }

    /** Cuerpo JSON decodificado. 400 si el JSON viene mal formado. */
    public function json(): array
    {
        if (trim($this->cuerpoCrudo) === '') {
            return [];
        }
        $datos = json_decode($this->cuerpoCrudo, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($datos)) {
            throw new HttpException(400, 'El cuerpo de la peticion no es un JSON valido');
        }
        return $datos;
    }
}

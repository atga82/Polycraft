<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Router REST: el recurso lo da la ruta y la operacion el metodo HTTP.
 *
 * ORDEN DE EJECUCION (de afuera hacia adentro):
 *   middlewares globales -> middlewares de la ruta -> accion del controlador
 * Las respuestas hacen el camino inverso, asi un middleware externo (CORS)
 * puede decorar tambien las respuestas de error de los internos.
 *
 * Un middleware se declara como clase ("App\Middleware\Autenticado") o con
 * argumentos ("App\Middleware\Rol:administrador,instructor").
 */
class Router
{
    /** @var array<int, array{metodo:string, regex:string, handler:array, middleware:array}> */
    private array $rutas = [];

    /** Middlewares que aplican a TODAS las peticiones (incluso 404/405), en orden. */
    private array $globales = [];

    public function __construct(private ?Container $contenedor = null)
    {
        $this->contenedor ??= new Container();
    }

    public function middlewareGlobal(string ...$specs): void
    {
        array_push($this->globales, ...$specs);
    }

    public function get(string $ruta, array $handler, array $mw = []): void    { $this->agregar('GET', $ruta, $handler, $mw); }
    public function post(string $ruta, array $handler, array $mw = []): void   { $this->agregar('POST', $ruta, $handler, $mw); }
    public function put(string $ruta, array $handler, array $mw = []): void    { $this->agregar('PUT', $ruta, $handler, $mw); }
    public function patch(string $ruta, array $handler, array $mw = []): void  { $this->agregar('PATCH', $ruta, $handler, $mw); }
    public function delete(string $ruta, array $handler, array $mw = []): void { $this->agregar('DELETE', $ruta, $handler, $mw); }

    private function agregar(string $metodo, string $ruta, array $handler, array $mw): void
    {
        // /api/cursos/{id} -> #^/api/cursos/(?P<id>[^/]+)$#
        $regex = preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', rtrim($ruta, '/') ?: '/');
        $this->rutas[] = [
            'metodo'     => $metodo,
            'regex'      => '#^' . $regex . '$#',
            'handler'    => $handler,
            'middleware' => $mw,
        ];
    }

    public function dispatch(Request $req): Response
    {
        $coincidencia = null;
        $params = [];
        $permitidos = [];

        foreach ($this->rutas as $r) {
            if (!preg_match($r['regex'], $req->ruta(), $m)) {
                continue;
            }
            if ($r['metodo'] === $req->metodo()) {
                $coincidencia = $r;
                $params = array_filter($m, 'is_string', ARRAY_FILTER_USE_KEY);
                break;
            }
            $permitidos[] = $r['metodo'];
        }

        if ($coincidencia !== null) {
            [$clase, $accion] = $coincidencia['handler'];
            $mw = array_merge($this->globales, $coincidencia['middleware']);
            $terminal = fn(Request $req): Response => $this->contenedor->resolver($clase)->$accion($req, $params);
        } else {
            // Sin ruta: igual pasa por los middlewares globales (para que CORS responda el preflight
            // y decore los 404/405).
            $mw = $this->globales;
            $allow = implode(', ', array_unique($permitidos));
            if ($permitidos && $req->metodo() === 'OPTIONS') {
                $terminal = fn(Request $req): Response => Response::sinContenido()->conHeader('Allow', $allow . ', OPTIONS');
            } elseif ($permitidos) {
                $terminal = function (Request $req) use ($allow): never {
                    throw new HttpException(405, 'Metodo no permitido para este recurso', [], ['Allow' => $allow]);
                };
            } else {
                $terminal = function (Request $req): never {
                    throw new HttpException(404, 'Recurso no encontrado');
                };
            }
        }

        return $this->construirCadena($mw, $terminal)($req);
    }

    /**
     * Cada capa convierte las HttpException de las capas internas en Response,
     * de modo que las capas externas siempre reciben una Response que pueden decorar.
     */
    private function construirCadena(array $specs, callable $terminal): callable
    {
        $cadena = $this->capturar($terminal);

        foreach (array_reverse($specs) as $spec) {
            $siguiente = $cadena;
            $mw = $this->instanciar($spec);
            $cadena = $this->capturar(fn(Request $req): Response => $mw->handle($req, $siguiente));
        }
        return $cadena;
    }

    private function capturar(callable $capa): callable
    {
        return function (Request $req) use ($capa): Response {
            try {
                return $capa($req);
            } catch (HttpException $e) {
                return Response::error($e->getStatus(), $e->getMessage(), $e->getDetalles(), $e->getHeaders());
            }
        };
    }

    private function instanciar(string $spec): Middleware
    {
        [$clase, $args] = array_pad(explode(':', $spec, 2), 2, null);
        return new $clase(...($args === null ? [] : explode(',', $args)));
    }
}

<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Curso;
use App\Models\Academia;
use DomainException;

/** Controlador REST del recurso cursos; no contiene SQL. */
class CursoController extends Controller
{
    public function listar(Request $req, array $params): Response
    {
        // El catálogo público nunca revela cursos dados de baja. El parámetro se conserva
        // para detectar filtros mal formados, pero desde esta ruta solo se permite activo=true.
        $activoSolicitado = strtolower((string)$req->query('activo', 'true'));
        if (!in_array($activoSolicitado, ['true', '1'], true)) {
            throw new HttpException(400, 'El catálogo público solo permite consultar cursos activos (activo=true)');
        }
        $filtros = [
            'buscar' => $req->query('buscar'),
            'categoria' => $req->query('categoria'),
            'instructor' => $req->query('instructor'),
            'fecha_desde' => $req->query('fecha_desde'),
            'fecha_hasta' => $req->query('fecha_hasta'),
            'activo' => 'true',
        ];
        foreach (['fecha_desde', 'fecha_hasta'] as $campo) {
            if ($filtros[$campo] !== null && $filtros[$campo] !== '' && !$this->fechaValida((string)$filtros[$campo])) {
                throw new HttpException(400, "El filtro $campo debe tener formato YYYY-MM-DD");
            }
        }
        try {
            $cursos = (new Curso())->listar($filtros);
        } catch (DomainException $e) {
            throw new HttpException(400, 'Filtros inválidos');
        }
        return Response::json(['total' => count($cursos), 'cursos' => $cursos]);
    }

    public function mostrar(Request $req, array $params): Response
    {
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) throw new HttpException(400, 'El id del curso debe ser un entero positivo');
        $curso = (new Curso())->porId((int)$id, false);
        if (!$curso) throw new HttpException(404, 'Curso no encontrado');
        $u = $req->atributo('usuario');
        $academia = new Academia();
        $mostrarContenido = $u['rol'] === 'administrador'
            || (int)$curso['instructor_id'] === (int)$u['id']
            || ($u['rol'] === 'estudiante' && $academia->inscripcion((int)$u['id'], (int)$id) !== null);
        if (!$curso['activo'] && !$mostrarContenido) {
            throw new HttpException(404, 'Curso dado de baja');
        }
        $curso['niveles'] = $academia->niveles((int)$id, $mostrarContenido);
        $curso['acceso_contenido'] = $mostrarContenido;
        return Response::json($curso);
    }

    public function crear(Request $req, array $params): Response
    {
        $usuario = $req->atributo('usuario');
        $d = $req->json();
        $this->exigir($d, ['titulo', 'descripcion', 'precio', 'categorias']);
        $errores = $this->validarDatos($d, true);
        if ($errores) throw new HttpException(422, 'Datos del curso inválidos', $errores);
        $model = new Curso();
        try {
            $id = $model->crear([
                'instructor_id' => (int)$usuario['id'],
                'titulo' => trim($d['titulo']),
                'descripcion' => trim($d['descripcion']),
                'imagen' => $d['imagen'] ?? 'default-course.png',
                'precio' => (float)$d['precio'],
                'categorias' => $d['categorias'],
            ]);
        } catch (DomainException $e) {
            throw $this->mapearErrorModelo($e);
        }
        return Response::creado($model->porId($id), '/api/cursos/' . $id);
    }

    public function actualizar(Request $req, array $params): Response
    {
        $usuario = $req->atributo('usuario');
        $id = filter_var($params['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$id) throw new HttpException(400, 'El id del curso debe ser un entero positivo');
        $model = new Curso();
        $curso = $model->porId((int)$id, false);
        if (!$curso) throw new HttpException(404, 'Curso no encontrado');
        if ($usuario['rol'] !== 'administrador' && (int)$curso['instructor_id'] !== (int)$usuario['id']) {
            throw new HttpException(403, 'Solo el instructor propietario o un administrador pueden modificar el curso');
        }
        $d = $req->json();
        if (!$d) throw new HttpException(400, 'Envía al menos un campo para actualizar');
        $errores = $this->validarDatos($d, false);
        if ($errores) throw new HttpException(422, 'Datos del curso inválidos', $errores);
        try {
            $model->actualizar((int)$id, $d);
        } catch (DomainException $e) {
            throw $this->mapearErrorModelo($e);
        }
        return Response::json($model->porId((int)$id, false));
    }

    private function validarDatos(array $d, bool $creacion): array
    {
        $errores = [];
        foreach (['titulo' => 150, 'descripcion' => 10000, 'imagen' => 255] as $campo => $max) {
            if (!array_key_exists($campo, $d)) continue;
            if (!is_string($d[$campo]) || trim($d[$campo]) === '' || poly_mb_strlen(trim($d[$campo])) > $max) {
                $errores[$campo] = "Debe ser texto no vacío de máximo $max caracteres";
            }
        }
        if ($creacion && (!is_string($d['titulo'] ?? null) || !is_string($d['descripcion'] ?? null))) {
            $errores['titulo'] = 'El título y la descripción deben ser texto';
        }
        if (array_key_exists('precio', $d)) {
            if (!is_numeric($d['precio']) || (float)$d['precio'] < 0 || (float)$d['precio'] > 99999999.99) {
                $errores['precio'] = 'Debe ser un número mayor o igual a 0';
            }
        } elseif ($creacion) {
            $errores['precio'] = 'Campo obligatorio';
        }
        if (array_key_exists('activo', $d) && !is_bool($d['activo']) && !in_array($d['activo'], [0, 1, '0', '1'], true)) {
            $errores['activo'] = 'Debe ser booleano';
        }
        if (array_key_exists('categorias', $d) && !is_array($d['categorias'])) {
            $errores['categorias'] = 'Debe ser un arreglo de IDs de categorías';
        } elseif ($creacion && empty($d['categorias'])) {
            $errores['categorias'] = 'Selecciona al menos una categoría';
        }
        return $errores;
    }

    private function mapearErrorModelo(DomainException $e): HttpException
    {
        return match ($e->getMessage()) {
            'categorias_requeridas' => new HttpException(422, 'Cada curso debe pertenecer al menos a una categoría'),
            'categorias_invalidas' => new HttpException(422, 'La lista de categorías debe contener IDs enteros positivos'),
            'categoria_no_existe' => new HttpException(422, 'Una o más categorías no existen'),
            'sin_cambios' => new HttpException(400, 'No hay campos válidos para modificar'),
            default => new HttpException(400, 'No se pudieron validar las categorías del curso'),
        };
    }

    private function fechaValida(string $fecha): bool
    {
        $d = \DateTime::createFromFormat('!Y-m-d', $fecha);
        return $d !== false && $d->format('Y-m-d') === $fecha;
    }
}

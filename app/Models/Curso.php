<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;
use PDO;
use DomainException;

/** Persistencia del catalogo de cursos. Las consultas SQL viven exclusivamente aqui. */
class Curso extends Model
{
    public function listar(array $filtros = []): array
    {
        $sql = 'SELECT c.id, c.instructor_id, c.titulo, c.descripcion, c.imagen,
                       c.precio, c.activo, c.fecha_creacion,
                       u.nombre_completo AS instructor,
                       (SELECT GROUP_CONCAT(cat.nombre ORDER BY cat.nombre SEPARATOR ", ")
                          FROM curso_categoria cc JOIN categorias cat ON cat.id = cc.categoria_id
                         WHERE cc.curso_id = c.id) AS categorias,
                       (SELECT COALESCE(AVG(co.calificacion), 0) FROM comentarios co
                         WHERE co.curso_id = c.id AND co.eliminado = 0) AS promedio_calificacion
                  FROM cursos c JOIN usuarios u ON u.id = c.instructor_id WHERE u.activo = 1';
        $params = [];

        if (array_key_exists('activo', $filtros) && $filtros['activo'] !== null && $filtros['activo'] !== '') {
            $activo = filter_var($filtros['activo'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($activo === null) {
                throw new DomainException('filtro_activo_invalido');
            }
            $sql .= ' AND c.activo = :activo';
            $params[':activo'] = $activo ? 1 : 0;
        }
        if (!empty($filtros['buscar'])) {
            $sql .= ' AND (c.titulo LIKE :titulo OR c.descripcion LIKE :descripcion OR u.nombre_completo LIKE :instructor_buscar)';
            $term = '%' . trim((string)$filtros['buscar']) . '%';
            $params[':titulo'] = $term;
            $params[':descripcion'] = $term;
            $params[':instructor_buscar'] = $term;
        }
        if (!empty($filtros['categoria'])) {
            $sql .= ' AND EXISTS (SELECT 1 FROM curso_categoria cc JOIN categorias cat ON cat.id = cc.categoria_id
                                   WHERE cc.curso_id = c.id AND (cat.nombre = :categoria OR cat.id = :categoria_id))';
            $params[':categoria'] = (string)$filtros['categoria'];
            $params[':categoria_id'] = ctype_digit((string)$filtros['categoria']) ? (int)$filtros['categoria'] : -1;
        }
        if (!empty($filtros['instructor'])) {
            $sql .= ' AND (u.nombre_completo LIKE :instructor_nombre OR u.email = :instructor_email)';
            $params[':instructor_nombre'] = '%' . trim((string)$filtros['instructor']) . '%';
            $params[':instructor_email'] = trim((string)$filtros['instructor']);
        }
        if (!empty($filtros['fecha_desde'])) {
            $sql .= ' AND c.fecha_creacion >= :fecha_desde';
            $params[':fecha_desde'] = (string)$filtros['fecha_desde'] . ' 00:00:00';
        }
        if (!empty($filtros['fecha_hasta'])) {
            $sql .= ' AND c.fecha_creacion < DATE_ADD(:fecha_hasta, INTERVAL 1 DAY)';
            $params[':fecha_hasta'] = (string)$filtros['fecha_hasta'];
        }
        $sql .= ' ORDER BY c.fecha_creacion DESC, c.id DESC';

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return array_map([$this, 'normalizarCurso'], $st->fetchAll());
    }

    public function porId(int $id, bool $soloActivos = true): ?array
    {
        $sql = 'SELECT c.id, c.instructor_id, c.titulo, c.descripcion, c.imagen,
                       c.precio, c.activo, c.fecha_creacion,
                       u.nombre_completo AS instructor,
                       (SELECT GROUP_CONCAT(cat.nombre ORDER BY cat.nombre SEPARATOR ", ")
                          FROM curso_categoria cc JOIN categorias cat ON cat.id = cc.categoria_id
                         WHERE cc.curso_id = c.id) AS categorias,
                       (SELECT COALESCE(AVG(co.calificacion), 0) FROM comentarios co
                         WHERE co.curso_id = c.id AND co.eliminado = 0) AS promedio_calificacion
                  FROM cursos c JOIN usuarios u ON u.id = c.instructor_id WHERE c.id = :id';
        if ($soloActivos) {
            $sql .= ' AND c.activo = 1 AND u.activo = 1';
        }
        $st = $this->db->prepare($sql);
        $st->execute([':id' => $id]);
        $curso = $st->fetch();
        if (!$curso) return null;

        $stNiveles = $this->db->prepare('SELECT id, curso_id, numero, titulo, descripcion, video_url, precio
                                          FROM niveles WHERE curso_id = :id ORDER BY numero ASC');
        $stNiveles->execute([':id' => $id]);
        $curso['niveles'] = $stNiveles->fetchAll();
        return $this->normalizarCurso($curso);
    }

    public function crear(array $datos): int
    {
        $categorias = $this->validarCategorias($datos['categorias'] ?? []);
        if (!$categorias) throw new DomainException('categorias_requeridas');

        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare(
                'INSERT INTO cursos (instructor_id, titulo, descripcion, imagen, precio, activo, fecha_creacion)
                 VALUES (:instructor, :titulo, :descripcion, :imagen, :precio, 1, NOW())'
            );
            $st->execute([
                ':instructor' => (int)$datos['instructor_id'],
                ':titulo' => trim((string)$datos['titulo']),
                ':descripcion' => trim((string)$datos['descripcion']),
                ':imagen' => trim((string)($datos['imagen'] ?? '')) ?: 'default-course.png',
                ':precio' => number_format((float)$datos['precio'], 2, '.', ''),
            ]);
            $id = (int)$this->db->lastInsertId();
            $stCat = $this->db->prepare('INSERT INTO curso_categoria (curso_id, categoria_id) VALUES (:curso, :categoria)');
            foreach ($categorias as $catId) {
                $stCat->execute([':curso' => $id, ':categoria' => $catId]);
            }
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function actualizar(int $id, array $datos): void
    {
        $sets = [];
        $params = [':id' => $id];
        if (array_key_exists('titulo', $datos)) { $sets[] = 'titulo = :titulo'; $params[':titulo'] = trim((string)$datos['titulo']); }
        if (array_key_exists('descripcion', $datos)) { $sets[] = 'descripcion = :descripcion'; $params[':descripcion'] = trim((string)$datos['descripcion']); }
        if (array_key_exists('imagen', $datos)) { $sets[] = 'imagen = :imagen'; $params[':imagen'] = trim((string)$datos['imagen']); }
        if (array_key_exists('precio', $datos)) { $sets[] = 'precio = :precio'; $params[':precio'] = number_format((float)$datos['precio'], 2, '.', ''); }
        if (array_key_exists('activo', $datos)) { $sets[] = 'activo = :activo'; $params[':activo'] = $datos['activo'] ? 1 : 0; }
        $categorias = array_key_exists('categorias', $datos) ? $this->validarCategorias($datos['categorias']) : null;
        if ($categorias !== null && !$categorias) throw new DomainException('categorias_requeridas');
        if (!$sets && $categorias === null) throw new DomainException('sin_cambios');

        $this->db->beginTransaction();
        try {
            if ($sets) {
                $st = $this->db->prepare('UPDATE cursos SET ' . implode(', ', $sets) . ' WHERE id = :id');
                $st->execute($params);
            }
            if ($categorias !== null) {
                $del = $this->db->prepare('DELETE FROM curso_categoria WHERE curso_id = :id');
                $del->execute([':id' => $id]);
                $ins = $this->db->prepare('INSERT INTO curso_categoria (curso_id, categoria_id) VALUES (:curso, :categoria)');
                foreach ($categorias as $catId) $ins->execute([':curso' => $id, ':categoria' => $catId]);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function cursoPagado(int $id): ?array
    {
        $st = $this->db->prepare(
            'SELECT c.id, c.titulo, c.precio, c.activo
               FROM cursos c
               JOIN usuarios u ON u.id = c.instructor_id
              WHERE c.id = :id AND c.activo = 1 AND u.activo = 1'
        );
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    private function validarCategorias(mixed $categorias): array
    {
        if (!is_array($categorias)) throw new DomainException('categorias_invalidas');
        $ids = array_values(array_unique(array_filter(array_map(static function ($v) {
            return filter_var($v, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
        }, $categorias))));
        if (count($ids) !== count($categorias)) throw new DomainException('categorias_invalidas');
        if (!$ids) return [];
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $st = $this->db->prepare("SELECT id FROM categorias WHERE id IN ($placeholders)");
        $st->execute($ids);
        $encontradas = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN));
        sort($ids); sort($encontradas);
        if ($ids !== $encontradas) throw new DomainException('categoria_no_existe');
        return $ids;
    }

    private function normalizarCurso(array $curso): array
    {
        if (array_key_exists('activo', $curso)) $curso['activo'] = (bool)$curso['activo'];
        if (array_key_exists('precio', $curso)) $curso['precio'] = (float)$curso['precio'];
        if (array_key_exists('promedio_calificacion', $curso)) $curso['promedio_calificacion'] = round((float)$curso['promedio_calificacion'], 2);
        if (isset($curso['niveles'])) {
            $curso['niveles'] = array_map(static function (array $nivel): array {
                $nivel['precio'] = (float)$nivel['precio'];
                return $nivel;
            }, $curso['niveles']);
        }
        return $curso;
    }
}

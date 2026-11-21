<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use App\Contracts\AcademiaRepositoryInterface;
use PDO;

/** Modelos de aprendizaje: niveles, inscripciones, progreso y diploma. */
class Academia extends Model implements AcademiaRepositoryInterface
{
    public function curso(int $id): ?array
    {
        $q = $this->db->prepare('SELECT id, instructor_id, titulo, precio, activo FROM cursos WHERE id = ?');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    public function nivel(int $id): ?array
    {
        $q = $this->db->prepare('SELECT n.*, c.instructor_id, c.activo AS curso_activo
          FROM niveles n JOIN cursos c ON c.id = n.curso_id WHERE n.id = ?');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    public function inscripcion(int $estudiante, int $curso): ?array
    {
        $q = $this->db->prepare('SELECT * FROM inscripciones WHERE estudiante_id = ? AND curso_id = ?');
        $q->execute([$estudiante, $curso]);
        return $q->fetch() ?: null;
    }

    public function crearNivel(int $cursoId, array $d): int
    {
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare('SELECT COALESCE(MAX(numero), 0) + 1 FROM niveles WHERE curso_id = ?');
            $q->execute([$cursoId]);
            $numero = (int)$q->fetchColumn();
            $q = $this->db->prepare('INSERT INTO niveles
                (curso_id, numero, titulo, descripcion, contenido, video_url, precio)
                VALUES (?, ?, ?, ?, ?, ?, ?)');
            $q->execute([$cursoId, $numero, $d['titulo'], $d['descripcion'] ?? null,
                $d['contenido'] ?? null, $d['video_url'], $d['precio']]);
            $id = (int)$this->db->lastInsertId();
            $this->db->commit();
            return $id;
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function editarNivel(int $id, array $campos): void
    {
        $set = [];
        $params = [];
        foreach (['titulo','descripcion','contenido','video_url','precio'] as $campo) {
            if (array_key_exists($campo, $campos)) {
                $set[] = $campo . ' = ?';
                $params[] = $campos[$campo];
            }
        }
        if (!$set) return;
        $params[] = $id;
        $q = $this->db->prepare('UPDATE niveles SET ' . implode(', ', $set) . ' WHERE id = ?');
        $q->execute($params);
    }

    public function borrarNivel(int $id): bool
    {
        $nivel = $this->nivel($id);
        if (!$nivel) return false;
        $q = $this->db->prepare('SELECT COUNT(*) FROM inscripciones WHERE curso_id = ?');
        $q->execute([(int)$nivel['curso_id']]);
        if ((int)$q->fetchColumn() > 0) return false;
        $q = $this->db->prepare('DELETE FROM niveles WHERE id = ?');
        $q->execute([$id]);
        $this->db->prepare('UPDATE niveles SET numero = numero - 1 WHERE curso_id = ? AND numero > ?')
            ->execute([(int)$nivel['curso_id'], (int)$nivel['numero']]);
        return true;
    }

    public function niveles(int $cursoId, bool $mostrarPrivado): array
    {
        $q = $this->db->prepare('SELECT id, curso_id, numero, titulo, descripcion, precio, contenido, video_url
          FROM niveles WHERE curso_id = ? ORDER BY numero');
        $q->execute([$cursoId]);
        $niveles = $q->fetchAll();
        $recursos = $this->db->prepare('SELECT id, tipo, titulo, url FROM recursos_nivel WHERE nivel_id = ? ORDER BY id');
        foreach ($niveles as &$nivel) {
            $nivel['precio'] = (float)$nivel['precio'];
            // Los niveles con costo cero son muestras públicas para cualquier usuario registrado.
            if ($mostrarPrivado || $nivel['precio'] <= 0) {
                $recursos->execute([(int)$nivel['id']]);
                $nivel['recursos'] = $recursos->fetchAll();
            } else {
                unset($nivel['video_url'], $nivel['contenido']);
            }
        }
        return $niveles;
    }

    public function agregarRecurso(int $nivelId, array $d): int
    {
        $q = $this->db->prepare('INSERT INTO recursos_nivel (nivel_id, tipo, titulo, url) VALUES (?, ?, ?, ?)');
        $q->execute([$nivelId, $d['tipo'], $d['titulo'], $d['url']]);
        return (int)$this->db->lastInsertId();
    }

    public function borrarRecurso(int $id): ?array
    {
        $q = $this->db->prepare('SELECT r.id, r.nivel_id, n.curso_id, c.instructor_id
            FROM recursos_nivel r JOIN niveles n ON n.id=r.nivel_id
            JOIN cursos c ON c.id=n.curso_id WHERE r.id = ?');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    public function eliminarRecurso(int $id): void
    {
        $this->db->prepare('DELETE FROM recursos_nivel WHERE id = ?')->execute([$id]);
    }

    public function inscribirGratis(int $estudiante, int $curso): int
    {
        $q = $this->db->prepare('INSERT INTO inscripciones (estudiante_id, curso_id) VALUES (?, ?)');
        $q->execute([$estudiante, $curso]);
        return (int)$this->db->lastInsertId();
    }

    public function completarNivel(int $estudianteId, int $nivelId): array
    {
        $this->db->beginTransaction();
        try {
            $q = $this->db->prepare('SELECT i.id, i.curso_id, i.fecha_terminacion, n.curso_id AS nivel_curso
                FROM niveles n JOIN inscripciones i ON i.curso_id=n.curso_id
                WHERE n.id=? AND i.estudiante_id=? FOR UPDATE');
            $q->execute([$nivelId, $estudianteId]);
            $i = $q->fetch();
            if (!$i) throw new \DomainException('no_inscrito');
            $inscripcionId = (int)$i['id'];
            $this->db->prepare('INSERT IGNORE INTO progreso_niveles (inscripcion_id,nivel_id) VALUES (?,?)')
                ->execute([$inscripcionId, $nivelId]);
            $this->db->prepare('UPDATE inscripciones SET ultimo_acceso=NOW() WHERE id=?')->execute([$inscripcionId]);
            $q = $this->db->prepare('SELECT COUNT(*) FROM niveles WHERE curso_id=?');
            $q->execute([(int)$i['curso_id']]);
            $total = (int)$q->fetchColumn();
            $q = $this->db->prepare('SELECT COUNT(*) FROM progreso_niveles WHERE inscripcion_id=?');
            $q->execute([$inscripcionId]);
            $terminados = (int)$q->fetchColumn();
            if ($total > 0 && $terminados >= $total) {
                $this->db->prepare('UPDATE inscripciones
                    SET fecha_terminacion=COALESCE(fecha_terminacion, NOW()) WHERE id=?')
                    ->execute([$inscripcionId]);
                $hex = bin2hex(random_bytes(16));
                $codigo = substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-'
                    . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
                $this->db->prepare('INSERT IGNORE INTO certificados (inscripcion_id,codigo) VALUES (?,?)')
                    ->execute([$inscripcionId, $codigo]);
            }
            $q = $this->db->prepare('SELECT id, codigo FROM certificados WHERE inscripcion_id=?');
            $q->execute([$inscripcionId]);
            $cert = $q->fetch() ?: null;
            $this->db->commit();
            return ['inscripcion_id' => $inscripcionId, 'niveles_completados' => $terminados,
                'niveles_totales' => $total, 'terminado' => $cert !== null, 'certificado' => $cert];
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            throw $e;
        }
    }

    public function kardex(int $estudiante, array $f = []): array
    {
        $sql = 'SELECT i.id AS inscripcion_id, i.curso_id, c.titulo, c.imagen, c.precio,
                c.activo, i.fecha_inscripcion, i.ultimo_acceso, i.fecha_terminacion,
                (SELECT ce.id FROM certificados ce WHERE ce.inscripcion_id=i.id) AS certificado_id,
                (SELECT COUNT(*) FROM niveles n WHERE n.curso_id=c.id) AS total_niveles,
                (SELECT COUNT(*) FROM progreso_niveles pn WHERE pn.inscripcion_id=i.id) AS completados
                FROM inscripciones i JOIN cursos c ON c.id=i.curso_id WHERE i.estudiante_id = :e';
        $params = [':e'=>$estudiante];
        if (!empty($f['fecha_desde'])) { $sql .= ' AND i.fecha_inscripcion >= :fd'; $params[':fd']=$f['fecha_desde'].' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $sql .= ' AND i.fecha_inscripcion < DATE_ADD(:fh, INTERVAL 1 DAY)'; $params[':fh']=$f['fecha_hasta']; }
        if (!empty($f['categoria'])) {
            $sql .= ' AND EXISTS(SELECT 1 FROM curso_categoria cc WHERE cc.curso_id=c.id AND cc.categoria_id=:cat)';
            $params[':cat']=(int)$f['categoria'];
        }
        if (($f['terminados'] ?? '') === 'true') $sql .= ' AND i.fecha_terminacion IS NOT NULL';
        if (($f['activos'] ?? '') === 'true') $sql .= ' AND c.activo=1';
        $q = $this->db->prepare($sql . ' ORDER BY i.fecha_inscripcion DESC, i.id DESC');
        $q->execute($params);
        return array_map(static function ($r) {
            $r['activo']=(bool)$r['activo'];
            $r['precio']=(float)$r['precio'];
            $r['total_niveles']=(int)$r['total_niveles'];
            $r['completados']=(int)$r['completados'];
            $r['porcentaje']=$r['total_niveles'] > 0 ? round(100*$r['completados']/$r['total_niveles'], 1) : 0;
            return $r;
        }, $q->fetchAll());
    }

    public function certificado(int $id): ?array
    {
        $q = $this->db->prepare('SELECT ce.id, ce.codigo, ce.fecha_emision, i.estudiante_id,
                i.fecha_terminacion, a.nombre_completo AS alumno, c.titulo AS curso,
                c.instructor_id, u.nombre_completo AS instructor
                FROM certificados ce JOIN inscripciones i ON i.id=ce.inscripcion_id
                JOIN usuarios a ON a.id=i.estudiante_id JOIN cursos c ON c.id=i.curso_id
                JOIN usuarios u ON u.id=c.instructor_id WHERE ce.id=? AND i.fecha_terminacion IS NOT NULL');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }
}

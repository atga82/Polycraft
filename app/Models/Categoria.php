<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;
use DomainException;

/** Operaciones de categorias; solo este modelo accede a MySQL. */
class Categoria extends Model
{
    public function listar(): array
    {
        $st = $this->db->query(
            'SELECT c.id, c.nombre, c.descripcion, c.creado_por, c.fecha_creacion,
                    (SELECT COUNT(*) FROM curso_categoria cc WHERE cc.categoria_id = c.id) AS total_cursos
               FROM categorias c ORDER BY c.nombre'
        );
        return $st->fetchAll();
    }

    public function crear(string $nombre, ?string $descripcion, int $creadoPor): int
    {
        $st = $this->db->prepare(
            'INSERT INTO categorias (nombre, descripcion, creado_por, fecha_creacion)
             VALUES (:nombre, :descripcion, :creador, NOW())'
        );
        try {
            $st->execute([':nombre' => $nombre, ':descripcion' => $descripcion, ':creador' => $creadoPor]);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') throw new DomainException('categoria_duplicada');
            throw $e;
        }
        return (int)$this->db->lastInsertId();
    }

    public function porId(int $id): ?array
    {
        $q=$this->db->prepare('SELECT id,nombre,descripcion FROM categorias WHERE id=?');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    public function actualizar(int $id, array $d): void
    {
        $sets=[]; $params=[];
        foreach (['nombre','descripcion'] as $campo) {
            if (array_key_exists($campo,$d)) {
                $sets[]=$campo.'=?';
                $params[]=$d[$campo];
            }
        }
        if (!$sets) return;
        $params[]=$id;
        try {
            $this->db->prepare('UPDATE categorias SET '.implode(',',$sets).' WHERE id=?')->execute($params);
        } catch (\PDOException $e) {
            if ($e->getCode()==='23000') throw new DomainException('categoria_duplicada');
            throw $e;
        }
    }

    public function borrar(int $id): bool
    {
        $q=$this->db->prepare('SELECT COUNT(*) FROM curso_categoria WHERE categoria_id=?');
        $q->execute([$id]);
        if ((int)$q->fetchColumn()>0) return false;
        $this->db->prepare('DELETE FROM categorias WHERE id=?')->execute([$id]);
        return true;
    }
}

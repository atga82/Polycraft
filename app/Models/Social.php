<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

/** Mensajería privada y reseñas. Consultas aisladas en el modelo. */
class Social extends Model
{
    public function comentarios(int $cursoId): array
    {
        $q = $this->db->prepare('SELECT co.id, co.curso_id, co.calificacion,
          CASE WHEN co.eliminado=1 THEN \'Mensaje eliminado por el administrador\' ELSE co.comentario END AS comentario,
          co.fecha_creacion, co.eliminado, u.nombre_completo AS autor, u.avatar
          FROM comentarios co JOIN usuarios u ON u.id=co.usuario_id
          WHERE co.curso_id = ? ORDER BY co.fecha_creacion DESC, co.id DESC');
        $q->execute([$cursoId]);
        return array_map(static function ($r) {
            $r['eliminado'] = (bool)$r['eliminado'];
            if ($r['eliminado']) { $r['calificacion']=null; $r['autor']='Moderación'; $r['avatar']='default.png'; }
            return $r;
        }, $q->fetchAll());
    }

    public function puedeComentar(int $usuarioId, int $cursoId): bool
    {
        $q = $this->db->prepare('SELECT 1 FROM inscripciones WHERE estudiante_id=?
          AND curso_id=? AND fecha_terminacion IS NOT NULL');
        $q->execute([$usuarioId,$cursoId]);
        return (bool)$q->fetchColumn();
    }

    public function crearComentario(int $usuarioId, int $cursoId, int $calificacion, string $comentario): int
    {
        $q = $this->db->prepare('INSERT INTO comentarios (curso_id,usuario_id,calificacion,comentario)
          VALUES (?,?,?,?)');
        $q->execute([$cursoId,$usuarioId,$calificacion,$comentario]);
        return (int)$this->db->lastInsertId();
    }

    public function comentario(int $id): ?array
    {
        $q = $this->db->prepare('SELECT id, eliminado FROM comentarios WHERE id=?');
        $q->execute([$id]);
        return $q->fetch() ?: null;
    }

    public function eliminarComentario(int $id, int $admin, string $causa): void
    {
        $q = $this->db->prepare('UPDATE comentarios SET eliminado=1, fecha_eliminacion=NOW(),
            causa_eliminacion=?, eliminado_por=? WHERE id=? AND eliminado=0');
        $q->execute([$causa,$admin,$id]);
    }

    public function usuariosParaChat(int $yo): array
    {
        $q = $this->db->prepare('SELECT id,nombre_completo,avatar FROM usuarios
            WHERE id<>? AND activo=1 ORDER BY nombre_completo LIMIT 200');
        $q->execute([$yo]);
        return $q->fetchAll();
    }

    public function usuarioActivo(int $id): bool
    {
        $q = $this->db->prepare('SELECT 1 FROM usuarios WHERE id=? AND activo=1');
        $q->execute([$id]);
        return (bool)$q->fetchColumn();
    }

    public function conversacion(int $yo, int $otro): array
    {
        $q = $this->db->prepare('SELECT m.id,m.remitente_id,m.destinatario_id,m.curso_id,
             m.contenido,m.fecha_envio,m.leido, u.nombre_completo AS remitente, u.avatar
            FROM mensajes m JOIN usuarios u ON u.id=m.remitente_id
            WHERE (m.remitente_id=:a AND m.destinatario_id=:b)
               OR (m.remitente_id=:c AND m.destinatario_id=:d)
            ORDER BY m.fecha_envio ASC, m.id ASC LIMIT 300');
        $q->execute([':a'=>$yo,':b'=>$otro,':c'=>$otro,':d'=>$yo]);
        $rows=$q->fetchAll();
        $this->db->prepare('UPDATE mensajes SET leido=1
            WHERE remitente_id=? AND destinatario_id=? AND leido=0')->execute([$otro,$yo]);
        return $rows;
    }

    public function enviar(int $remitente, int $destinatario, string $contenido, ?int $cursoId): int
    {
        $q=$this->db->prepare('INSERT INTO mensajes (remitente_id,destinatario_id,curso_id,contenido)
            VALUES (?,?,?,?)');
        $q->execute([$remitente,$destinatario,$cursoId,$contenido]);
        return (int)$this->db->lastInsertId();
    }
}

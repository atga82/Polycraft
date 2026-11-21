<?php
declare(strict_types=1);
namespace App\Models;

use App\Core\Model;

/** Consultas agregadas de reportes: sin SQL en controladores ni vistas. */
class Reporte extends Model
{
    public function ventas(int $instructor, array $f): array
    {
        $sql = 'SELECT c.id,c.titulo,c.fecha_creacion,c.activo,c.precio,
            (SELECT COUNT(*) FROM inscripciones i WHERE i.curso_id=c.id) AS alumnos,
            (SELECT COALESCE(AVG((SELECT COUNT(*) FROM progreso_niveles pn
                WHERE pn.inscripcion_id=i.id)),0)
                FROM inscripciones i WHERE i.curso_id=c.id) AS niveles_promedio,
            (SELECT COALESCE(SUM(p.monto),0) FROM pagos p
                JOIN inscripciones i ON i.id=p.inscripcion_id WHERE i.curso_id=c.id) AS ingresos
            FROM cursos c WHERE c.instructor_id=:instructor';
        $params=[':instructor'=>$instructor];
        if (!empty($f['fecha_desde'])) { $sql.=' AND c.fecha_creacion>=:desde'; $params[':desde']=$f['fecha_desde'].' 00:00:00'; }
        if (!empty($f['fecha_hasta'])) { $sql.=' AND c.fecha_creacion < DATE_ADD(:hasta,INTERVAL 1 DAY)'; $params[':hasta']=$f['fecha_hasta']; }
        if (!empty($f['categoria'])) {
            $sql.=' AND EXISTS(SELECT 1 FROM curso_categoria cc WHERE cc.curso_id=c.id AND cc.categoria_id=:cat)';
            $params[':cat']=(int)$f['categoria'];
        }
        if (($f['activos']??'')==='true') $sql.=' AND c.activo=1';
        $q=$this->db->prepare($sql.' ORDER BY c.fecha_creacion DESC,c.id DESC');
        $q->execute($params);
        $cursos=$q->fetchAll();
        foreach ($cursos as &$c) {
            $c['activo']=(bool)$c['activo'];
            $c['alumnos']=(int)$c['alumnos'];
            $c['niveles_promedio']=round((float)$c['niveles_promedio'],2);
            $c['ingresos']=(float)$c['ingresos'];
        }
        $ids=array_map(static fn($c)=>(int)$c['id'],$cursos);
        $pagos=[];
        if ($ids) {
            $marcas=implode(',',array_fill(0,count($ids),'?'));
            $q=$this->db->prepare('SELECT fp.nombre AS forma_pago, SUM(p.monto) AS total
                FROM pagos p JOIN formas_pago fp ON fp.id=p.forma_pago_id
                JOIN inscripciones i ON i.id=p.inscripcion_id
                WHERE i.curso_id IN ('.$marcas.') GROUP BY fp.id,fp.nombre');
            $q->execute($ids);
            $pagos=$q->fetchAll();
        }
        return ['cursos'=>$cursos,'total_ingresos'=>array_sum(array_column($cursos,'ingresos')),
            'por_forma_pago'=>$pagos];
    }

    public function detalleVenta(int $instructor, int $cursoId): ?array
    {
        $q=$this->db->prepare('SELECT id,titulo,activo,precio FROM cursos WHERE id=? AND instructor_id=?');
        $q->execute([$cursoId,$instructor]);
        $curso=$q->fetch();
        if (!$curso) return null;
        $q=$this->db->prepare('SELECT i.id AS inscripcion_id,u.nombre_completo AS alumno,
            i.fecha_inscripcion,i.ultimo_acceso,i.fecha_terminacion,
            (SELECT COUNT(*) FROM progreso_niveles pn WHERE pn.inscripcion_id=i.id) AS niveles_completados,
            (SELECT COUNT(*) FROM niveles n WHERE n.curso_id=i.curso_id) AS niveles_totales,
            COALESCE((SELECT SUM(p.monto) FROM pagos p WHERE p.inscripcion_id=i.id),0) AS pagado,
            COALESCE((SELECT GROUP_CONCAT(DISTINCT fp.nombre ORDER BY fp.nombre SEPARATOR ", ")
              FROM pagos p JOIN formas_pago fp ON fp.id=p.forma_pago_id WHERE p.inscripcion_id=i.id), \'Gratuito\') AS formas_pago
            FROM inscripciones i JOIN usuarios u ON u.id=i.estudiante_id
            WHERE i.curso_id=? ORDER BY i.fecha_inscripcion');
        $q->execute([$cursoId]);
        $alumnos=$q->fetchAll();
        foreach ($alumnos as &$a) {
            $a['niveles_completados']=(int)$a['niveles_completados'];
            $a['niveles_totales']=(int)$a['niveles_totales'];
            $a['pagado']=(float)$a['pagado'];
        }
        return ['curso'=>$curso,'alumnos'=>$alumnos,'total_ingresos'=>array_sum(array_column($alumnos,'pagado'))];
    }

    public function usuarios(string $rol): array
    {
        if ($rol==='instructor') {
            $sql='SELECT u.id,u.email,u.nombre_completo AS nombre,u.fecha_registro,
                (SELECT COUNT(*) FROM cursos c WHERE c.instructor_id=u.id) AS cursos_ofrecidos,
                (SELECT COALESCE(SUM(p.monto),0) FROM pagos p
                 JOIN inscripciones i ON i.id=p.inscripcion_id
                 JOIN cursos c ON c.id=i.curso_id WHERE c.instructor_id=u.id) AS ganancias
                FROM usuarios u JOIN roles r ON r.id=u.rol_id
                WHERE u.activo=1 AND r.nombre=\'instructor\' ORDER BY u.nombre_completo';
        } else {
            $sql='SELECT u.id,u.email,u.nombre_completo AS nombre,u.fecha_registro,
                (SELECT COUNT(*) FROM inscripciones i WHERE i.estudiante_id=u.id) AS cursos_inscritos,
                (SELECT COUNT(*) FROM inscripciones i WHERE i.estudiante_id=u.id
                 AND i.fecha_terminacion IS NOT NULL) AS cursos_terminados
                FROM usuarios u JOIN roles r ON r.id=u.rol_id
                WHERE u.activo=1 AND r.nombre=\'estudiante\' ORDER BY u.nombre_completo';
        }
        $rows=$this->db->query($sql)->fetchAll();
        if ($rol==='estudiante') {
            foreach ($rows as &$r) {
                $r['porcentaje_terminados']=(int)$r['cursos_inscritos'] > 0
                    ? round(100*(int)$r['cursos_terminados']/(int)$r['cursos_inscritos'],1):0;
            }
        }
        return $rows;
    }
}

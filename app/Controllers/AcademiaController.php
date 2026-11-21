<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Contracts\AcademiaRepositoryInterface;

/** API del flujo de aprendizaje. Los datos se consultan solo desde el modelo. */
class AcademiaController extends Controller
{
    public function __construct(private AcademiaRepositoryInterface $academia)
    {
    }

    private function id(mixed $v): int
    {
        $id = filter_var($v, FILTER_VALIDATE_INT, ['options'=>['min_range'=>1]]);
        if (!$id) throw new HttpException(400, 'El identificador debe ser un entero positivo');
        return (int)$id;
    }

    private function autorizacionCurso(Request $req, int $cursoId): array
    {
        $curso = $this->academia->curso($cursoId);
        if (!$curso) throw new HttpException(404, 'Curso no encontrado');
        $u = $req->atributo('usuario');
        if ($u['rol'] !== 'administrador' && (int)$curso['instructor_id'] !== (int)$u['id']) {
            throw new HttpException(403, 'Solo el instructor propietario o administrador puede gestionar el curso');
        }
        return $curso;
    }

    private function validarNivel(array $d, bool $crear): array
    {
        $errores = [];
        foreach (['titulo'=>150,'descripcion'=>10000,'contenido'=>30000,'video_url'=>255] as $campo=>$max) {
            if (!array_key_exists($campo,$d)) {
                if ($crear && in_array($campo,['titulo','video_url'],true)) $errores[$campo]='Obligatorio';
                continue;
            }
            if (!is_string($d[$campo]) || strlen(trim($d[$campo])) > $max ||
                (in_array($campo,['titulo','video_url'],true) && trim($d[$campo])==='')) {
                $errores[$campo]='Texto inválido o demasiado largo';
            }
        }
        if (isset($d['video_url']) && is_string($d['video_url']) &&
            !preg_match('~^(https?://[^\\s]+|/uploads/courses/[^\\s]+)$~i', $d['video_url'])) {
            $errores['video_url']='Debe ser una URL HTTP(S) o un video subido al portal';
        }
        if ($crear || array_key_exists('precio',$d)) {
            if (!isset($d['precio'])) { $d['precio']=0; }
            if (!is_numeric($d['precio']) || (float)$d['precio'] < 0 || (float)$d['precio'] > 99999999) {
                $errores['precio']='El costo debe ser un número entre 0 y 99999999';
            }
        }
        return $errores;
    }

    public function niveles(Request $req, array $p): Response
    {
        $cursoId=$this->id($p['id'] ?? null);
        $curso=$this->academia->curso($cursoId);
        if (!$curso) throw new HttpException(404,'Curso no encontrado');
        $u=$req->atributo('usuario');
        $privado=$u['rol']==='administrador' || (int)$curso['instructor_id']===(int)$u['id'] ||
            ($u['rol']==='estudiante' && $this->academia->inscripcion((int)$u['id'],$cursoId)!==null);
        if (!$curso['activo'] && !$privado) throw new HttpException(404,'Curso no disponible');
        return Response::json(['acceso_contenido'=>$privado,'niveles'=>$this->academia->niveles($cursoId,$privado)]);
    }

    public function crearNivel(Request $req, array $p): Response
    {
        $cursoId=$this->id($p['id'] ?? null);
        $this->autorizacionCurso($req,$cursoId);
        $d=$req->json();
        $err=$this->validarNivel($d,true);
        if ($err) throw new HttpException(422,'Nivel inválido',$err);
        $id=$this->academia->crearNivel($cursoId,[
            'titulo'=>trim($d['titulo']), 'descripcion'=>trim((string)($d['descripcion']??'')),
            'contenido'=>trim((string)($d['contenido']??'')),
            'video_url'=>trim($d['video_url']), 'precio'=>(float)($d['precio']??0)
        ]);
        return Response::creado(['id'=>$id,'curso_id'=>$cursoId],'/api/niveles/'.$id);
    }

    public function editarNivel(Request $req, array $p): Response
    {
        $id=$this->id($p['id']??null);
        $nivel=$this->academia->nivel($id);
        if (!$nivel) throw new HttpException(404,'Nivel no encontrado');
        $this->autorizacionCurso($req,(int)$nivel['curso_id']);
        $d=$req->json();
        $err=$this->validarNivel($d,false);
        if ($err) throw new HttpException(422,'Nivel inválido',$err);
        if (!array_intersect(array_keys($d),['titulo','descripcion','contenido','video_url','precio'])) {
            throw new HttpException(422,'No se recibieron campos modificables');
        }
        $this->academia->editarNivel($id,$d);
        return Response::json(['id'=>$id,'actualizado'=>true]);
    }

    public function eliminarNivel(Request $req, array $p): Response
    {
        $id=$this->id($p['id']??null);
        $nivel=$this->academia->nivel($id);
        if (!$nivel) throw new HttpException(404,'Nivel no encontrado');
        $this->autorizacionCurso($req,(int)$nivel['curso_id']);
        if (!$this->academia->borrarNivel($id)) {
            throw new HttpException(409,'No se puede quitar un nivel de un curso con alumnos inscritos');
        }
        return Response::sinContenido();
    }

    public function agregarRecurso(Request $req, array $p): Response
    {
        $id=$this->id($p['id']??null);
        $nivel=$this->academia->nivel($id);
        if (!$nivel) throw new HttpException(404,'Nivel no encontrado');
        $this->autorizacionCurso($req,(int)$nivel['curso_id']);
        $d=$req->json();
        if (!in_array($d['tipo']??null,['pdf','enlace','imagen'],true) ||
            !is_string($d['titulo']??null) || trim($d['titulo'])==='' || strlen($d['titulo'])>150 ||
            !is_string($d['url']??null) || strlen($d['url'])>255 ||
            !preg_match('~^(https?://[^\\s]+|/uploads/courses/[^\\s]+)$~i',$d['url'])) {
            throw new HttpException(422,'Recurso inválido: tipo, título y URL HTTP(S) obligatorios');
        }
        $nuevo=$this->academia->agregarRecurso($id,$d);
        return Response::creado(['id'=>$nuevo,'nivel_id'=>$id]);
    }

    public function eliminarRecurso(Request $req, array $p): Response
    {
        $id=$this->id($p['id']??null);
        $r=$this->academia->borrarRecurso($id);
        if (!$r) throw new HttpException(404,'Recurso no encontrado');
        $this->autorizacionCurso($req,(int)$r['curso_id']);
        $this->academia->eliminarRecurso($id);
        return Response::sinContenido();
    }

    public function inscribir(Request $req, array $p): Response
    {
        $d=$req->json();
        $cursoId=$this->id($d['curso_id']??null);
        $curso=$this->academia->curso($cursoId);
        if (!$curso || !$curso['activo']) throw new HttpException(404,'Curso activo no encontrado');
        if ((float)$curso['precio']>0) throw new HttpException(409,'Es un curso de pago: usa /api/compras');
        $u=$req->atributo('usuario');
        if ($this->academia->inscripcion((int)$u['id'],$cursoId)) {
            throw new HttpException(409,'Ya estás inscrito en este curso');
        }
        $id=$this->academia->inscribirGratis((int)$u['id'],$cursoId);
        return Response::creado(['inscripcion_id'=>$id,'curso_id'=>$cursoId,'mensaje'=>'Inscripción gratuita creada']);
    }

    public function kardex(Request $req, array $p): Response
    {
        $f=[];
        foreach (['fecha_desde','fecha_hasta'] as $campo) {
            $v=$req->query($campo);
            if ($v!==null && $v!=='') {
                $fecha=\DateTime::createFromFormat('!Y-m-d',(string)$v);
                if (!$fecha || $fecha->format('Y-m-d')!==$v) throw new HttpException(400,'Fecha inválida en '.$campo);
                $f[$campo]=$v;
            }
        }
        if ($req->query('categoria')!==null && $req->query('categoria')!=='') {
            $f['categoria']=$this->id($req->query('categoria'));
        }
        foreach (['terminados','activos'] as $campo) {
            $v=$req->query($campo);
            if ($v!==null && !in_array($v,['true','false'],true)) {
                throw new HttpException(400,'Filtro inválido: '.$campo);
            }
            if ($v!==null) $f[$campo]=$v;
        }
        $u=$req->atributo('usuario');
        return Response::json($this->academia->kardex((int)$u['id'],$f));
    }

    public function completar(Request $req, array $p): Response
    {
        $id=$this->id($p['id']??null);
        $u=$req->atributo('usuario');
        try {
            return Response::json($this->academia->completarNivel((int)$u['id'],$id));
        } catch (\DomainException) {
            throw new HttpException(403,'Solo puedes completar niveles de cursos en los que estás inscrito');
        }
    }

    public function certificado(Request $req, array $p): Response
    {
        $id=$this->id($p['id']??null);
        $cert=$this->academia->certificado($id);
        if (!$cert) throw new HttpException(404,'Certificado no encontrado');
        $u=$req->atributo('usuario');
        if ($u['rol']!=='administrador' && (int)$cert['estudiante_id']!==(int)$u['id'] &&
            (int)$cert['instructor_id']!==(int)$u['id']) {
            throw new HttpException(403,'No tienes acceso a este certificado');
        }
        unset($cert['estudiante_id'],$cert['instructor_id']);
        return Response::json($cert);
    }
}

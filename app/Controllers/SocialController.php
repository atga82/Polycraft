<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Social;
use App\Models\Academia;
use PDOException;

class SocialController extends Controller
{
    public function __construct(private ?Social $social = null)
    {
        $this->social ??= new Social();
    }

    private function id(mixed $v): int
    {
        $id=filter_var($v,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) throw new HttpException(400,'Identificador inválido');
        return (int)$id;
    }

    public function listarComentarios(Request $req, array $p): Response
    {
        $curso=$this->id($p['id']??null);
        if (!(new Academia())->curso($curso)) throw new HttpException(404,'Curso inexistente');
        return Response::json($this->social->comentarios($curso));
    }

    public function comentar(Request $req, array $p): Response
    {
        $curso=$this->id($p['id']??null);
        $d=$req->json();
        $cal=$d['calificacion']??null;
        $texto=$d['comentario']??null;
        if (!is_int($cal) || $cal<1 || $cal>5 || !is_string($texto) ||
            trim($texto)==='' || poly_mb_strlen($texto)>2000) {
            throw new HttpException(422,'La reseña requiere 1 a 5 estrellas y un comentario de máximo 2000 caracteres');
        }
        $u=$req->atributo('usuario');
        if (!$this->social->puedeComentar((int)$u['id'],$curso)) {
            throw new HttpException(403,'Solo puede comentar el estudiante que terminó este curso');
        }
        try {
            $id=$this->social->crearComentario((int)$u['id'],$curso,$cal,trim($texto));
        } catch (PDOException $e) {
            if ($e->getCode()==='23000') throw new HttpException(409,'Ya comentaste este curso');
            throw $e;
        }
        return Response::creado(['id'=>$id,'curso_id'=>$curso,'calificacion'=>$cal]);
    }

    public function moderar(Request $req, array $p): Response
    {
        $id=$this->id($p['id']??null);
        $d=$req->json();
        if (!is_string($d['causa']??null) || poly_mb_strlen(trim($d['causa']))<5 ||
            poly_mb_strlen($d['causa'])>255) {
            throw new HttpException(422,'Indica la causa de moderación (5 a 255 caracteres)');
        }
        $c=$this->social->comentario($id);
        if (!$c) throw new HttpException(404,'Comentario no encontrado');
        if ($c['eliminado']) throw new HttpException(409,'El comentario ya está eliminado');
        $this->social->eliminarComentario($id,(int)$req->atributo('usuario')['id'],trim($d['causa']));
        return Response::json(['id'=>$id,'mensaje'=>'Comentario retirado; la leyenda sustituta queda visible']);
    }

    public function contactos(Request $req, array $p): Response
    {
        $u=$req->atributo('usuario');
        return Response::json($this->social->usuariosParaChat((int)$u['id']));
    }

    public function mensajes(Request $req, array $p): Response
    {
        $otro=$this->id($req->query('con'));
        $u=$req->atributo('usuario');
        if ((int)$u['id']===$otro || !$this->social->usuarioActivo($otro)) {
            throw new HttpException(404,'Usuario destinatario no encontrado');
        }
        return Response::json($this->social->conversacion((int)$u['id'],$otro));
    }

    public function enviar(Request $req, array $p): Response
    {
        $d=$req->json();
        $dest=$this->id($d['destinatario_id']??null);
        $texto=$d['contenido']??null;
        $u=$req->atributo('usuario');
        if ($dest===(int)$u['id'] || !$this->social->usuarioActivo($dest)) {
            throw new HttpException(422,'Destinatario inválido');
        }
        if (!is_string($texto) || trim($texto)==='' || poly_mb_strlen($texto)>4000) {
            throw new HttpException(422,'Escribe un mensaje de 1 a 4000 caracteres');
        }
        $curso=null;
        if (isset($d['curso_id'])) {
            $curso=$this->id($d['curso_id']);
            if (!(new Academia())->curso($curso)) throw new HttpException(404,'Curso no encontrado');
        }
        $id=$this->social->enviar((int)$u['id'],$dest,trim($texto),$curso);
        return Response::creado(['id'=>$id,'mensaje'=>'Mensaje enviado']);
    }
}

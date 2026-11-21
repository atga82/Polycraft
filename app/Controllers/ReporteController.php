<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Reporte;

class ReporteController extends Controller
{
    public function __construct(private ?Reporte $reporte = null)
    {
        $this->reporte ??= new Reporte();
    }

    private function filtros(Request $req): array
    {
        $f=[];
        foreach (['fecha_desde','fecha_hasta'] as $campo) {
            $v=$req->query($campo);
            if ($v===null || $v==='') continue;
            $dt=\DateTime::createFromFormat('!Y-m-d',(string)$v);
            if (!$dt || $dt->format('Y-m-d')!==$v) throw new HttpException(400,'Fecha inválida: '.$campo);
            $f[$campo]=$v;
        }
        if ($req->query('categoria')!==null && $req->query('categoria')!=='') {
            $cat=filter_var($req->query('categoria'),FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
            if (!$cat) throw new HttpException(400,'Categoría inválida');
            $f['categoria']=(int)$cat;
        }
        $activo=$req->query('activos');
        if ($activo!==null && !in_array($activo,['true','false'],true)) {
            throw new HttpException(400,'Valor inválido en activos');
        }
        if ($activo!==null) $f['activos']=$activo;
        return $f;
    }

    public function ventas(Request $req, array $p): Response
    {
        $u=$req->atributo('usuario');
        return Response::json($this->reporte->ventas((int)$u['id'],$this->filtros($req)));
    }

    public function detalleVenta(Request $req, array $p): Response
    {
        $id=filter_var($p['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) throw new HttpException(400,'Curso inválido');
        $u=$req->atributo('usuario');
        $r=$this->reporte->detalleVenta((int)$u['id'],(int)$id);
        if (!$r) throw new HttpException(404,'Curso no encontrado o no pertenece al instructor');
        return Response::json($r);
    }

    public function usuarios(Request $req, array $p): Response
    {
        $tipo=$req->query('tipo','instructor');
        if (!in_array($tipo,['instructor','estudiante'],true)) {
            throw new HttpException(400,'El tipo debe ser instructor o estudiante');
        }
        return Response::json(['tipo'=>$tipo,'usuarios'=>$this->reporte->usuarios($tipo)]);
    }
}

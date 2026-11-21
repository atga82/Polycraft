<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Archivo;
use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;

/** POST multipart: tipo=imagen|pdf|video, archivo=archivo binario. */
class ArchivoController extends Controller
{
    public function subir(Request $req, array $p): Response
    {
        $tipo=$req->post('tipo');
        if (!is_string($tipo) || !in_array($tipo,['imagen','pdf','video'],true)) {
            throw new HttpException(422,'El campo tipo debe ser imagen, pdf o video');
        }
        $url=Archivo::guardar($req->archivo('archivo'),$tipo);
        return Response::creado(['url'=>$url,'tipo'=>$tipo]);
    }
}

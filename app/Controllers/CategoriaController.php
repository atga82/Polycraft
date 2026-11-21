<?php
declare(strict_types=1);
namespace App\Controllers;

use App\Core\Controller;
use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Models\Categoria;
use DomainException;

class CategoriaController extends Controller
{
    public function listar(Request $req, array $params): Response
    {
        return Response::json((new Categoria())->listar());
    }

    public function crear(Request $req, array $params): Response
    {
        $d = $req->json();
        $this->exigir($d, ['nombre']);
        if (!is_string($d['nombre']) || poly_mb_strlen(trim($d['nombre'])) < 2 || poly_mb_strlen(trim($d['nombre'])) > 80) {
            throw new HttpException(422, 'El nombre debe contener entre 2 y 80 caracteres');
        }
        if (isset($d['descripcion']) && (!is_string($d['descripcion']) || poly_mb_strlen($d['descripcion']) > 2000)) {
            throw new HttpException(422, 'La descripción debe ser texto de máximo 2000 caracteres');
        }
        $u = $req->atributo('usuario');
        try {
            $id = (new Categoria())->crear(trim($d['nombre']), isset($d['descripcion']) ? trim($d['descripcion']) : null, (int)$u['id']);
        } catch (DomainException) {
            throw new HttpException(409, 'Ya existe una categoría con ese nombre');
        }
        return Response::creado(['id' => $id, 'nombre' => trim($d['nombre']), 'descripcion' => $d['descripcion'] ?? null], '/api/categorias');
    }

    public function actualizar(Request $req, array $params): Response
    {
        $id=filter_var($params['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) throw new HttpException(400,'Categoría inválida');
        $modelo=new Categoria();
        if (!$modelo->porId((int)$id)) throw new HttpException(404,'Categoría no encontrada');
        $d=$req->json();
        if (!$d || !array_intersect(['nombre','descripcion'],array_keys($d))) {
            throw new HttpException(422,'Envía nombre o descripción');
        }
        if (array_key_exists('nombre',$d) &&
            (!is_string($d['nombre']) || poly_mb_strlen(trim($d['nombre']))<2 ||
            poly_mb_strlen($d['nombre'])>80)) {
            throw new HttpException(422,'Nombre inválido (2 a 80 caracteres)');
        }
        if (array_key_exists('descripcion',$d) &&
            (!is_string($d['descripcion']) || poly_mb_strlen($d['descripcion'])>2000)) {
            throw new HttpException(422,'Descripción inválida');
        }
        try {
            $modelo->actualizar((int)$id,$d);
        } catch (DomainException) {
            throw new HttpException(409,'Otra categoría tiene ese nombre');
        }
        return Response::json($modelo->porId((int)$id));
    }

    public function eliminar(Request $req, array $params): Response
    {
        $id=filter_var($params['id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
        if (!$id) throw new HttpException(400,'Categoría inválida');
        $modelo=new Categoria();
        if (!$modelo->porId((int)$id)) throw new HttpException(404,'Categoría no encontrada');
        if (!$modelo->borrar((int)$id)) {
            throw new HttpException(409,'La categoría contiene cursos; reasígnalos antes de eliminarla');
        }
        return Response::sinContenido();
    }
}

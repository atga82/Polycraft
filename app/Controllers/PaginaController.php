<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\Usuario;

/** Paginas HTML del portal. Cada metodo solo elige la vista; la logica vive en el modelo. */
class PaginaController extends Controller
{
    public function inicio(Request $req, array $p): Response     { return $this->pagina('cursos/Menu', $req); }
    public function curso(Request $req, array $p): Response      { return $this->pagina('cursos/Curso', $req); }
    public function kardex(Request $req, array $p): Response     { return $this->pagina('estudiante/Kardex', $req); }
    public function ventas(Request $req, array $p): Response     { return $this->pagina('instructor/Ventas', $req); }
    public function crearCurso(Request $req, array $p): Response { return $this->pagina('instructor/RegisterCourse', $req); }
    public function chat(Request $req, array $p): Response       { return $this->pagina('mensajes/Chat', $req); }
    public function reportes(Request $req, array $p): Response   { return $this->pagina('admin/Reports', $req); }
    public function certificado(Request $req, array $p): Response { return $this->pagina('cursos/Certificado', $req); }
    public function perfil(Request $req, array $p): Response      { return $this->pagina('auth/Perfil', $req); }

    private function pagina(string $vista, Request $req): Response
    {
        $id = Session::usuarioId();
        $usuario = $id !== null ? (new Usuario())->porId($id) : null;
        if ($usuario !== null && !(int)$usuario['activo']) {
            $usuario = null;
        }
        return Response::html(View::render($vista, $req, [
            'usuario' => $usuario,
            'csrf'    => Session::csrf(),
        ]));
    }
}

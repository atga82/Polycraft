<?php
declare(strict_types=1);

use App\Controllers\AccesoController;
use App\Controllers\AcademiaController;
use App\Controllers\ArchivoController;
use App\Controllers\SocialController;
use App\Controllers\ReporteController;
use App\Controllers\CategoriaController;
use App\Controllers\CompraController;
use App\Controllers\CursoController;
use App\Controllers\AuthController;
use App\Controllers\HealthController;
use App\Controllers\PaginaController;
use App\Controllers\PerfilController;
use App\Controllers\UsuarioController;
use App\Middleware\Autenticado;
use App\Middleware\Cors;
use App\Middleware\Rol;

/** @var \App\Core\Router $router */

// Middleware global (se ejecuta primero en TODA peticion): CORS.
$router->middlewareGlobal(Cors::class);

// Orden en rutas protegidas:  Cors -> Autenticado (sesion) -> Rol (permiso) -> controlador

// ---- Paginas HTML (por ahora publicas; la proteccion por rol llega cuando se conecten a la API) ----
$router->get('/',             [PaginaController::class, 'inicio']);

// ---- Acceso con formularios clasicos (login, registro, logout) ----
$router->get('/login',        [AccesoController::class, 'formLogin']);
$router->post('/login',       [AccesoController::class, 'login']);
$router->get('/registro',     [AccesoController::class, 'formRegistro']);
$router->post('/registro',    [AccesoController::class, 'registro']);
$router->post('/logout',      [AccesoController::class, 'logout']);

$router->get('/curso',        [PaginaController::class, 'curso']);
$router->get('/kardex',       [PaginaController::class, 'kardex']);
$router->get('/ventas',       [PaginaController::class, 'ventas']);
$router->get('/crear-curso',  [PaginaController::class, 'crearCurso']);
$router->get('/chat',         [PaginaController::class, 'chat']);
$router->get('/reportes',     [PaginaController::class, 'reportes']);
$router->get('/certificado',  [PaginaController::class, 'certificado']);
$router->get('/perfil',       [PaginaController::class, 'perfil']);

// ---- API publica ----
$router->get('/api/health',           [HealthController::class, 'index']);
$router->post('/api/auth/registro',   [AuthController::class, 'registro']);
$router->post('/api/auth/login',      [AuthController::class, 'login']);
$router->post('/api/auth/logout',     [AuthController::class, 'logout']);

// ---- Catalogo publico ----
$router->get('/api/cursos',            [CursoController::class, 'listar']);
$router->get('/api/cursos/{id}',       [CursoController::class, 'mostrar'], [Autenticado::class, Rol::permitir('estudiante', 'instructor', 'administrador')]);
$router->get('/api/categorias',        [CategoriaController::class, 'listar']);

// ---- Cualquier usuario con sesion ----
$router->get('/api/perfil',           [PerfilController::class, 'mostrar'],     [Autenticado::class]);
$router->patch('/api/perfil',         [PerfilController::class, 'actualizar'],  [Autenticado::class]);
$router->put('/api/perfil',           [PerfilController::class, 'actualizar'],  [Autenticado::class]);
$router->post('/api/perfil/avatar',   [PerfilController::class, 'avatar'],      [Autenticado::class]);

// ---- Cursos: instructor propietario o administrador ----
$instructor = [Autenticado::class, Rol::permitir('instructor', 'administrador')];
$router->post('/api/cursos',           [CursoController::class, 'crear'],       $instructor);
$router->post('/api/archivos',          [ArchivoController::class, 'subir'],     $instructor);
$router->patch('/api/cursos/{id}',      [CursoController::class, 'actualizar'],  $instructor);

// ---- Solo administrador ----
$admin = [Autenticado::class, Rol::permitir('administrador')];
$router->post('/api/categorias',       [CategoriaController::class, 'crear'],   $admin);
$router->patch('/api/categorias/{id}', [CategoriaController::class, 'actualizar'], $admin);
$router->delete('/api/categorias/{id}', [CategoriaController::class, 'eliminar'], $admin);
$router->get('/api/usuarios',                [UsuarioController::class, 'listar'], $admin);
$router->patch('/api/usuarios/{id}/estado',  [UsuarioController::class, 'estado'], $admin);

// ---- Compras pagadas: el monto y el estado se validan contra MySQL/Mercado Pago ----
$estudiante = [Autenticado::class, Rol::permitir('estudiante')];
$router->post('/api/compras',          [CompraController::class, 'crear'],       $estudiante);
$router->get('/api/compras/{id}',       [CompraController::class, 'mostrar'],     $estudiante);

// ---- Tercera entrega: niveles, recursos, progreso y diploma ----
$router->get('/api/cursos/{id}/niveles', [AcademiaController::class, 'niveles'], [Autenticado::class]);
$router->post('/api/cursos/{id}/niveles', [AcademiaController::class, 'crearNivel'], $instructor);
$router->patch('/api/niveles/{id}', [AcademiaController::class, 'editarNivel'], $instructor);
$router->delete('/api/niveles/{id}', [AcademiaController::class, 'eliminarNivel'], $instructor);
$router->post('/api/niveles/{id}/recursos', [AcademiaController::class, 'agregarRecurso'], $instructor);
$router->delete('/api/recursos/{id}', [AcademiaController::class, 'eliminarRecurso'], $instructor);
$router->post('/api/inscripciones', [AcademiaController::class, 'inscribir'], $estudiante);
$router->get('/api/inscripciones', [AcademiaController::class, 'kardex'], $estudiante);
$router->post('/api/niveles/{id}/completar', [AcademiaController::class, 'completar'], $estudiante);
$router->get('/api/certificados/{id}', [AcademiaController::class, 'certificado'], [Autenticado::class]);

// ---- Comentarios calificados, moderación y mensajes privados ----
$router->get('/api/cursos/{id}/comentarios', [SocialController::class, 'listarComentarios'], [Autenticado::class]);
$router->post('/api/cursos/{id}/comentarios', [SocialController::class, 'comentar'], $estudiante);
$router->delete('/api/comentarios/{id}', [SocialController::class, 'moderar'], $admin);
$router->get('/api/contactos', [SocialController::class, 'contactos'], [Autenticado::class]);
$router->get('/api/mensajes', [SocialController::class, 'mensajes'], [Autenticado::class]);
$router->post('/api/mensajes', [SocialController::class, 'enviar'], [Autenticado::class]);

// ---- Reportes de la tercera entrega ----
$router->get('/api/ventas', [ReporteController::class, 'ventas'], [Autenticado::class, Rol::permitir('instructor')]);
$router->get('/api/ventas/{id}', [ReporteController::class, 'detalleVenta'], [Autenticado::class, Rol::permitir('instructor')]);
$router->get('/api/reportes/usuarios', [ReporteController::class, 'usuarios'], $admin);

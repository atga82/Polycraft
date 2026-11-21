<?php
declare(strict_types=1);
/**
 * Prueba de sustitución real de dependencias: no conecta ni escribe a MySQL.
 * php scripts/probar_inyeccion.php
 */
require_once dirname(__DIR__) . '/app/bootstrap.php';

use App\Contracts\AcademiaRepositoryInterface;
use App\Controllers\AcademiaController;
use App\Core\Container;

final class FakeAcademia implements AcademiaRepositoryInterface
{
    public function curso(int $id): ?array { return ['id'=>$id,'instructor_id'=>99,'activo'=>1]; }
    public function nivel(int $id): ?array { return null; }
    public function inscripcion(int $estudiante, int $curso): ?array { return null; }
    public function crearNivel(int $cursoId, array $d): int { return 42; }
    public function editarNivel(int $id, array $campos): void {}
    public function borrarNivel(int $id): bool { return true; }
    public function niveles(int $cursoId, bool $mostrarPrivado): array { return []; }
    public function agregarRecurso(int $nivelId, array $d): int { return 43; }
    public function borrarRecurso(int $id): ?array { return null; }
    public function eliminarRecurso(int $id): void {}
    public function inscribirGratis(int $estudiante, int $curso): int { return 44; }
    public function completarNivel(int $estudianteId, int $nivelId): array { return []; }
    public function kardex(int $estudiante, array $f=[]): array { return []; }
    public function certificado(int $id): ?array { return null; }
}

$contenedor=new Container();
$contenedor->registrar(AcademiaController::class, static fn()=>new AcademiaController(new FakeAcademia()));
$controlador=$contenedor->resolver(AcademiaController::class);
$reflexion=new ReflectionClass($controlador);
$propiedad=$reflexion->getProperty('academia');
$propiedad->setAccessible(true);
$inyeccion=$propiedad->getValue($controlador);

if (!$controlador instanceof AcademiaController || !$inyeccion instanceof FakeAcademia ||
    $inyeccion->crearNivel(1,[])!==42) {
    fwrite(STDERR,"FALLO: la implementación no pudo sustituirse.\n");
    exit(1);
}
echo "OK: AcademiaController recibió FakeAcademia vía constructor/container, sin tocar controlador ni BD.\n";

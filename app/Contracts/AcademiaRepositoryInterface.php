<?php
declare(strict_types=1);
namespace App\Contracts;

/**
 * Puerto de datos de aprendizaje. Un FakeAcademia puede sustituir a
 * MySQL sin cambiar el controlador, útil para pruebas unitarias.
 */
interface AcademiaRepositoryInterface
{
    public function curso(int $id): ?array;
    public function nivel(int $id): ?array;
    public function inscripcion(int $estudiante, int $curso): ?array;
    public function crearNivel(int $cursoId, array $d): int;
    public function editarNivel(int $id, array $campos): void;
    public function borrarNivel(int $id): bool;
    public function niveles(int $cursoId, bool $mostrarPrivado): array;
    public function agregarRecurso(int $nivelId, array $d): int;
    public function borrarRecurso(int $id): ?array;
    public function eliminarRecurso(int $id): void;
    public function inscribirGratis(int $estudiante, int $curso): int;
    public function completarNivel(int $estudianteId, int $nivelId): array;
    public function kardex(int $estudiante, array $f = []): array;
    public function certificado(int $id): ?array;
}

<?php
declare(strict_types=1);
namespace App\Core;

/** Contenedor pequeño de inyección de dependencias del portal. */
class Container
{
    private array $fabricas = [];

    public function registrar(string $clase, callable $fabrica): void
    {
        $this->fabricas[$clase] = $fabrica;
    }

    public function resolver(string $clase): object
    {
        if (isset($this->fabricas[$clase])) {
            $objeto = ($this->fabricas[$clase])($this);
            if (!$objeto instanceof $clase) {
                throw new \LogicException('La dependencia inyectada no coincide con su contrato');
            }
            return $objeto;
        }
        return new $clase();
    }
}

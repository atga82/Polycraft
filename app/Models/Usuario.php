<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Model;

class Usuario extends Model
{
    public const MAX_INTENTOS = 3;

    /** Hash falso para que "email inexistente" tarde lo mismo que "password incorrecto". */
    public const HASH_FALSO = '$2y$10$BW8wJZxSY0RffO.TEVrDpOxS0VZMKdnN/mQ22/EK1mXriMWUF0Cbi';

    private const SELECT = 'SELECT u.*, r.nombre AS rol
                            FROM usuarios u JOIN roles r ON r.id = u.rol_id';

    public function rolId(string $nombre): ?int
    {
        $st = $this->db->prepare('SELECT id FROM roles WHERE nombre = :n');
        $st->execute([':n' => $nombre]);
        $id = $st->fetchColumn();
        return $id === false ? null : (int)$id;
    }

    /** Fila completa (incluye password_hash): solo para uso interno de autenticacion. */
    public function porEmail(string $email): ?array
    {
        $st = $this->db->prepare(self::SELECT . ' WHERE u.email = :e');
        $st->execute([':e' => $email]);
        return $st->fetch() ?: null;
    }

    public function porId(int $id): ?array
    {
        $st = $this->db->prepare(self::SELECT . ' WHERE u.id = :id');
        $st->execute([':id' => $id]);
        return $st->fetch() ?: null;
    }

    /** Listado para el administrador (sin hashes). $rol null = todos. */
    public function listar(?string $rol, int $limite, int $offset): array
    {
        $sql = self::SELECT . ($rol !== null ? ' WHERE r.nombre = :rol' : '')
             . ' ORDER BY u.id LIMIT :lim OFFSET :off';
        $st = $this->db->prepare($sql);
        if ($rol !== null) {
            $st->bindValue(':rol', $rol);
        }
        $st->bindValue(':lim', $limite, \PDO::PARAM_INT);
        $st->bindValue(':off', $offset, \PDO::PARAM_INT);
        $st->execute();
        return array_map([self::class, 'publicoAdmin'], $st->fetchAll());
    }

    /** Habilita o deshabilita; al habilitar tambien se reinicia el contador de intentos. */
    public function cambiarEstado(int $id, bool $activo): void
    {
        $st = $this->db->prepare('UPDATE usuarios SET activo = :a, intentos_fallidos = 0 WHERE id = :id');
        $st->bindValue(':a', $activo ? 1 : 0, \PDO::PARAM_INT);
        $st->bindValue(':id', $id, \PDO::PARAM_INT);
        $st->execute();
    }

    public function emailEnUso(string $email, int $exceptoId = 0): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM usuarios WHERE email = :e AND id <> :id');
        $st->execute([':e' => $email, ':id' => $exceptoId]);
        return (bool)$st->fetchColumn();
    }

    /** @throws \DomainException('email_duplicado') */
    public function crear(array $d): int
    {
        try {
            $st = $this->db->prepare(
                'INSERT INTO usuarios
                    (rol_id, nombre_completo, genero, fecha_nacimiento, avatar, email, password_hash, fecha_registro)
                 VALUES (:rol, :nombre, :genero, :nac, :avatar, :email, :hash, :fecha)'
            );
            $st->execute([
                ':rol'    => $d['rol_id'],
                ':nombre' => $d['nombre_completo'],
                ':genero' => $d['genero'],
                ':nac'    => $d['fecha_nacimiento'],
                ':avatar' => $d['avatar'] ?? 'default.png',
                ':email'  => $d['email'],
                ':hash'   => $d['password_hash'],
                ':fecha'  => date('Y-m-d H:i:s'),
            ]);
            return (int)$this->db->lastInsertId();
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {            // UNIQUE(email) violado
                throw new \DomainException('email_duplicado');
            }
            throw $e;
        }
    }

    /**
     * Suma un intento fallido y, si llega a MAX_INTENTOS, deshabilita la cuenta.
     * Es un solo UPDATE (atomico), asi dos peticiones simultaneas no se pisan.
     * Devuelve ['intentos' => int, 'activo' => bool].
     */
    public function registrarIntentoFallido(int $id): array
    {
        $st = $this->db->prepare(
            'UPDATE usuarios
                SET activo = CASE WHEN intentos_fallidos + 1 >= :max THEN 0 ELSE activo END,
                    intentos_fallidos = intentos_fallidos + 1
              WHERE id = :id'
        );
        // Enlazados como entero: si viajaran como texto la comparacion podria fallar en silencio.
        $st->bindValue(':max', self::MAX_INTENTOS, \PDO::PARAM_INT);
        $st->bindValue(':id', $id, \PDO::PARAM_INT);
        $st->execute();

        $st = $this->db->prepare('SELECT intentos_fallidos, activo FROM usuarios WHERE id = :id');
        $st->execute([':id' => $id]);
        $f = $st->fetch();
        return ['intentos' => (int)$f['intentos_fallidos'], 'activo' => (bool)$f['activo']];
    }

    public function reiniciarIntentos(int $id): void
    {
        $st = $this->db->prepare('UPDATE usuarios SET intentos_fallidos = 0 WHERE id = :id');
        $st->execute([':id' => $id]);
    }

    /**
     * Actualiza SOLO columnas permitidas y sella fecha_modificacion.
     * La llave primaria es id, por eso cambiar el email no rompe relaciones.
     * @throws \DomainException('email_duplicado')
     */
    public function actualizarPerfil(int $id, array $campos): void
    {
        $permitidos = ['nombre_completo', 'genero', 'fecha_nacimiento', 'email', 'password_hash', 'avatar'];
        $sets = [];
        $params = [':id' => $id, ':fecha' => date('Y-m-d H:i:s')];
        foreach ($campos as $col => $valor) {
            if (!in_array($col, $permitidos, true)) {
                continue;
            }
            $sets[] = "$col = :$col";
            $params[":$col"] = $valor;
        }
        if (!$sets) {
            return;
        }
        try {
            $st = $this->db->prepare(
                'UPDATE usuarios SET ' . implode(', ', $sets) . ', fecha_modificacion = :fecha WHERE id = :id'
            );
            $st->execute($params);
        } catch (\PDOException $e) {
            if ($e->getCode() === '23000') {
                throw new \DomainException('email_duplicado');
            }
            throw $e;
        }
    }

    /** Version segura de una fila: nunca expone el hash ni los intentos. */
    public static function publico(array $f): array
    {
        return [
            'id'                 => (int)$f['id'],
            'nombre_completo'    => $f['nombre_completo'],
            'email'              => $f['email'],
            'genero'             => $f['genero'],
            'fecha_nacimiento'   => $f['fecha_nacimiento'],
            'avatar'             => $f['avatar'],
            'rol'                => $f['rol'],
            'fecha_registro'     => $f['fecha_registro'],
            'fecha_modificacion' => $f['fecha_modificacion'],
        ];
    }

    /** Igual que publico() pero con el estado de la cuenta (solo lo ve el administrador). */
    public static function publicoAdmin(array $f): array
    {
        return self::publico($f) + [
            'activo'            => (bool)$f['activo'],
            'intentos_fallidos' => (int)$f['intentos_fallidos'],
        ];
    }
}

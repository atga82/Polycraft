<?php
declare(strict_types=1);

/** Conexion PDO centralizada. Puedes sobrescribir los valores en config/local.php. */
final class Database
{
    private static ?PDO $conexion = null;

    public static function conectar(): PDO
    {
        if (self::$conexion === null) {
            $host = getenv('DB_HOST') ?: '127.0.0.1';
            $port = getenv('DB_PORT') ?: '3306';
            $db   = getenv('DB_NAME') ?: 'portal_cursos';
            $user = getenv('DB_USER') ?: 'root';
            $pass = getenv('DB_PASSWORD');
            if ($pass === false) $pass = '';

            $dsn = "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4";
            try {
                self::$conexion = new PDO($dsn, $user, $pass, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]);
            } catch (PDOException $e) {
                error_log('[Polycraft] No fue posible conectar con MySQL: ' . $e->getMessage());
                throw new RuntimeException('No fue posible conectar con la base de datos.', 0, $e);
            }
        }
        return self::$conexion;
    }
}

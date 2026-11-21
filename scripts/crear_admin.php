<?php
declare(strict_types=1);

/**
 * Crea (una vez) el usuario administrador. El rol admin NO se puede obtener por registro publico.
 * Uso:  php scripts/crear_admin.php
 *       php scripts/crear_admin.php correo@dominio.com "Nombre Completo" "Contrasena1!"
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require __DIR__ . '/../app/bootstrap.php';

use App\Core\Validator;
use App\Models\Usuario;

$pregunta = function (string $texto) {
    fwrite(STDOUT, $texto);
    return trim((string)fgets(STDIN));
};

$email  = poly_mb_strtolower($argv[1] ?? $pregunta('Email: '));
$nombre = $argv[2] ?? $pregunta('Nombre completo: ');
$pass   = $argv[3] ?? $pregunta('Contrasena (8+, mayuscula, numero, especial): ');

$errores = array_filter([
    'email'    => Validator::email($email),
    'nombre'   => Validator::nombre($nombre),
    'password' => Validator::password($pass),
]);
if ($errores) {
    foreach ($errores as $campo => $msg) fwrite(STDERR, "$campo: $msg\n");
    exit(1);
}

$usuarios = new Usuario();
if ($usuarios->emailEnUso($email)) {
    fwrite(STDERR, "Ya existe un usuario con ese email\n");
    exit(1);
}

$id = $usuarios->crear([
    'rol_id'           => $usuarios->rolId('administrador'),
    'nombre_completo'  => $nombre,
    'genero'           => 'otro',
    'fecha_nacimiento' => '1990-01-01',
    'email'            => $email,
    'password_hash'    => password_hash($pass, PASSWORD_DEFAULT),
]);
echo "Administrador creado con id $id\n";

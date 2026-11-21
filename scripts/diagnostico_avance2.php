<?php
declare(strict_types=1);

/**
 * Diagnóstico local de solo lectura del avance 2.
 * Ejecuta: C:\xampp\php\php.exe scripts\diagnostico_avance2.php
 * No muestra credenciales, contraseñas ni datos personales.
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit;
}
require dirname(__DIR__) . '/app/bootstrap.php';

try {
    $db = Database::conectar();
    $conteos = [];
    foreach (['usuarios', 'categorias', 'cursos', 'inscripciones', 'compras', 'pagos'] as $tabla) {
        $conteos[$tabla] = (int)$db->query('SELECT COUNT(*) FROM ' . $tabla)->fetchColumn();
    }
    $cursos = $db->query(
        'SELECT c.id, c.titulo, c.activo, c.instructor_id, u.activo AS instructor_activo
           FROM cursos c JOIN usuarios u ON u.id = c.instructor_id
          ORDER BY c.id DESC LIMIT 15'
    )->fetchAll(PDO::FETCH_ASSOC);
    $compras = $db->query(
        'SELECT id, curso_id, estado, mp_status, mp_status_detail, mp_order_id IS NOT NULL AS tiene_orden
           FROM compras ORDER BY id DESC LIMIT 15'
    )->fetchAll(PDO::FETCH_ASSOC);
    $token = trim((string)(getenv('MP_ACCESS_TOKEN') ?: ''));
    echo json_encode([
        'ok' => true,
        'db' => getenv('DB_NAME') ?: 'portal_cursos',
        'tablas' => $conteos,
        'cursos' => $cursos,
        'compras' => $compras,
        'mercado_pago' => [
            'token_configurado' => $token !== '',
            'prefijo_app_usr' => str_starts_with($token, 'APP_USR'),
            'entorno_no_verificable_por_prefijo' => true,
            'curl' => function_exists('curl_init'),
        ],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . PHP_EOL;
} catch (Throwable $e) {
    fwrite(STDERR, 'Diagnóstico falló: ' . $e->getMessage() . PHP_EOL);
    exit(1);
}

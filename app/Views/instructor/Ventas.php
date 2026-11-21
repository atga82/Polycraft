<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Ventas | Polycraft</title><link rel="stylesheet" href="<?= e($base) ?>/assets/css/stylemenu.css">
<style>.pc-table{width:100%;border-collapse:collapse;background:#fff}.pc-table th,.pc-table td{padding:12px;border-bottom:1px solid #ddd;text-align:left}.pc-table th{background:#eff3fa;color:#253145}.pc-panel{background:#fff;padding:22px;margin:16px 0;border-radius:12px}.pc-alert{padding:10px;color:#253145}.pc-action{margin:4px}.pc-scroll{overflow-x:auto}.pc-form{display:flex;flex-wrap:wrap;gap:12px;align-items:end}.pc-form label{display:flex;flex-direction:column;gap:4px}.pc-form input,.pc-form select,.pc-form textarea{padding:8px;border:1px solid #aeb8c8;border-radius:6px}.pc-form button{padding:9px 16px}.pc-muted{color:#64748b}</style></head><body>
<nav class="navbar">
  <div class="navbar-left">
    <a href="<?= e($base) ?>/" class="logo">Polycraft</a>
    <a href="<?= e($base) ?>/" class="nav-link">Cursos</a>
    <a href="<?= e($base) ?>/crear-curso" class="nav-link">Crear curso</a>
    <a href="<?= e($base) ?>/kardex" class="nav-link">Kardex</a>
    <a href="<?= e($base) ?>/ventas" class="nav-link">Ventas</a>
    <a href="<?= e($base) ?>/chat" class="nav-link">Chat</a>
    <a href="<?= e($base) ?>/reportes" class="nav-link">Administración</a>
  </div>
  <div class="navbar-right"><?php include BASE_PATH . '/app/Views/partials/sesion.php'; ?></div>
</nav>
<main class="page-content" id="pc-ventas">
<h1 class="page-title">Ventas de mis cursos</h1>
<div class="pc-panel"><form id="ventas-filtros" class="pc-form">
<label>Curso creado desde <input type="date" name="fecha_desde"></label>
<label>Hasta <input type="date" name="fecha_hasta"></label>
<label>Categoría <select name="categoria"><option value="">Todas</option></select></label>
<label>Estado <select name="activos"><option value="false">Todos</option><option value="true">Solo activos</option></select></label>
<button class="btn btn-filled" type="submit">Filtrar</button></form></div>
<p id="ventas-estado" class="pc-alert" role="status"></p>
<div class="pc-panel pc-scroll"><h2>Resumen por curso</h2><table class="pc-table"><thead><tr><th>Curso</th><th>Alumnos</th><th>Niveles promedio</th><th>Ingresos</th><th>Detalle</th></tr></thead><tbody id="ventas-tabla"></tbody></table>
<p id="ventas-total"></p><div id="ventas-formas"></div></div>
<div class="pc-panel pc-scroll"><h2>Detalle por alumno</h2><p id="ventas-detalle-curso" class="pc-muted">Selecciona un curso en la tabla anterior.</p><table class="pc-table"><thead><tr><th>Alumno</th><th>Inscripción</th><th>Avance</th><th>Pagado</th><th>Forma de pago</th></tr></thead><tbody id="ventas-detalle-tabla"></tbody></table><p id="ventas-detalle-total"></p></div>
</main>
<script>
window.POLY_BASE = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.POLY_ROL = <?= json_encode($usuario['rol'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= e($base) ?>/assets/js/portal-avance3.js"></script>
</body></html>
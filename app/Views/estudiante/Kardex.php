<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mi Kardex | Polycraft</title><link rel="stylesheet" href="<?= e($base) ?>/assets/css/stylemenu.css">
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
<main class="page-content" id="pc-kardex">
<h1 class="page-title">Mi Kardex</h1>
<p>Consulta tus inscripciones, niveles terminados y diplomas.</p>
<div class="pc-panel"><form id="kardex-filtros" class="pc-form">
<label>Inscrito desde <input type="date" name="fecha_desde"></label>
<label>Hasta <input type="date" name="fecha_hasta"></label>
<label>Categoría <select name="categoria"><option value="">Todas</option></select></label>
<label>Progreso <select name="terminados"><option value="false">Todos</option><option value="true">Terminados</option></select></label>
<label>Cursos <select name="activos"><option value="false">Todos</option><option value="true">Solo activos</option></select></label>
<button class="btn btn-filled" type="submit">Filtrar</button></form></div>
<p class="pc-alert" id="kardex-estado" role="status"></p>
<div class="pc-panel pc-scroll"><table class="pc-table"><thead><tr><th>Curso</th><th>Inscripción</th><th>Último acceso</th><th>Terminación</th><th>Avance</th><th>Acción</th></tr></thead><tbody id="kardex-tabla"></tbody></table></div>
</main>
<script>
window.POLY_BASE = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.POLY_ROL = <?= json_encode($usuario['rol'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= e($base) ?>/assets/js/portal-avance3.js"></script>
</body></html>
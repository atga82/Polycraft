<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Administración y reportes | Polycraft</title><link rel="stylesheet" href="<?= e($base) ?>/assets/css/stylemenu.css">
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
<main class="page-content" id="pc-admin">
<h1 class="page-title">Panel de administración</h1>
<p>Solo disponible para administradores. Los cambios de estado y las eliminaciones requieren confirmación.</p>
<p id="admin-estado" class="pc-alert" role="status"></p>
<div class="pc-panel pc-scroll"><h2>Reporte de usuarios activos</h2><form id="reporte-filtros" class="pc-form">
<label>Tipo de usuario <select name="tipo"><option value="instructor">Instructor</option><option value="estudiante">Estudiante</option></select></label>
<button class="btn btn-filled">Generar reporte</button></form>
<table class="pc-table"><thead id="reporte-head"></thead><tbody id="reporte-tabla"></tbody></table></div>
<div class="pc-panel pc-scroll"><h2>Administrar categorías</h2>
<form id="categoria-form" class="pc-form"><input type="hidden" name="id">
<label>Nombre <input name="nombre" maxlength="80" required></label>
<label>Descripción <input name="descripcion" maxlength="2000"></label>
<button class="btn btn-filled">Guardar categoría</button><button type="button" id="categoria-cancelar">Cancelar edición</button></form>
<table class="pc-table"><thead><tr><th>Categoría</th><th>Descripción</th><th>Cursos</th><th>Acciones</th></tr></thead><tbody id="categoria-tabla"></tbody></table></div>
<div class="pc-panel pc-scroll"><h2>Bloqueo y desbloqueo de usuarios</h2>
<table class="pc-table"><thead><tr><th>Nombre</th><th>Correo</th><th>Rol</th><th>Estado</th><th>Acción</th></tr></thead><tbody id="usuarios-tabla"></tbody></table></div>
<div class="pc-panel pc-scroll"><h2>Moderación de comentarios</h2>
<form id="moderacion-form" class="pc-form"><label>Curso <select name="curso" id="moderacion-cursos"><option value="">Selecciona curso</option></select></label>
<button class="btn btn-filled">Ver reseñas</button></form>
<table class="pc-table"><thead><tr><th>Autor</th><th>Calificación</th><th>Comentario</th><th>Fecha</th><th>Acción</th></tr></thead><tbody id="moderacion-tabla"></tbody></table></div>
</main>
<script>
window.POLY_BASE = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.POLY_ROL = <?= json_encode($usuario['rol'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= e($base) ?>/assets/js/portal-avance3.js"></script>
</body></html>
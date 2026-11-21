<!DOCTYPE html>
<html lang="es">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mensajes | Polycraft</title><link rel="stylesheet" href="<?= e($base) ?>/assets/css/stylemenu.css">
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
<main class="page-content" id="pc-chat">
<h1 class="page-title">Mensajería privada</h1><p>Selecciona un usuario para enviarle mensajes. Solo las dos personas de una conversación pueden leerla.</p>
<p id="chat-estado" role="status" class="pc-alert"></p>
<div class="pc-panel"><form id="chat-selector" class="pc-form"><label>Conversar con <select id="chat-contacto" required><option value="">Selecciona una persona</option></select></label>
<button class="btn btn-filled">Abrir conversación</button></form></div>
<div class="pc-panel"><div id="chat-mensajes" style="min-height:190px;max-height:450px;overflow-y:auto"></div>
<form id="chat-envio" class="pc-form"><label style="flex:1">Mensaje <textarea id="chat-texto" maxlength="4000" rows="3" required placeholder="Escribe tu pregunta sobre un curso..."></textarea></label>
<button class="btn btn-filled">Enviar</button></form></div></main>
<script>
window.POLY_BASE = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
window.POLY_ROL = <?= json_encode($usuario['rol'] ?? '', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="<?= e($base) ?>/assets/js/portal-avance3.js"></script>
</body></html>
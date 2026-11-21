<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Certificado Polycraft</title>
<style>body{font-family:Georgia,serif;background:#eff3fa;margin:0;padding:40px;color:#193453}.diploma{max-width:900px;margin:auto;background:white;border:12px double #2c5175;padding:65px 40px;text-align:center;box-shadow:0 14px 30px #0002}.diploma h1{letter-spacing:3px;font-size:40px}.nombre{font-size:32px;color:#0d648b}.firma{margin-top:50px;border-top:1px solid #555;display:inline-block;padding-top:12px}button{margin:20px;padding:12px 20px}@media print{body{background:white;padding:0}button{display:none}.diploma{box-shadow:none}}</style></head>
<body><div class="diploma"><h1>POLYCRAFT</h1><h2>Certificado de finalización</h2>
<p>Se acredita que</p><p id="d-alumno" class="nombre">Cargando certificado...</p>
<p>ha completado satisfactoriamente el curso</p><h2 id="d-curso"></h2>
<p>Fecha de terminación: <strong id="d-fecha"></strong></p><p>Código: <span id="d-codigo"></span></p>
<p class="firma" id="d-instructor"></p></div>
<div style="text-align:center"><button onclick="window.print()">Imprimir / guardar como PDF</button>
<a href="<?= e($base) ?>/kardex">Volver a Kardex</a></div>
<script>window.POLY_BASE=<?= json_encode($base) ?>; window.POLY_ROL=<?= json_encode($usuario['rol']??'') ?>;</script>
<script src="<?= e($base) ?>/assets/js/portal-avance3.js"></script>
</body></html>
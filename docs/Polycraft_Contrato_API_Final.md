# Polycraft — Documentación final de la API propia

Referencia basada en `app/routes.php`, controladores y `app/Core/Response.php` (21/11/2026).
Base: `http://localhost/Polycraft-main/api`. Sesión: cookie `PHPSESSID`.
Éxito: `{"ok":true,"data":...}`. Error: `{"ok":false,"error":{"codigo":N,"mensaje":"...","detalles":{...}}}`.
Los valores de las muestras son ilustrativos y el JSON de datos puede incluir campos adicionales.

## Estado y autenticación

### `GET /api/health`
**Acceso:** Público. Comprobar servicio PHP y disponibilidad de la API.
**Petición:**
```http
GET /api/health  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"servicio": "Polycraft API","version": "1.0","hora": "2026-11-21T10:00:00-06:00"}}
```
**Errores:** 500.

### `POST /api/auth/registro`
**Acceso:** Público. Registrar estudiante o instructor; no se permite administrador público.
**Petición:**
```http
POST /api/auth/registro
Content-Type: application/json
{"rol": "estudiante","nombre_completo": "Estudiante Demo","email": "estudiante@example.test","genero": "otro","fecha_nacimiento": "2000-01-01","password": "ClaveSegura1!"}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"id": 16,"nombre_completo": "Estudiante Demo","email": "estudiante@example.test","rol": "estudiante","avatar": "default.png"}}
```
**Errores:** 409 email duplicado; 422 datos o rol inválidos.

### `POST /api/auth/login`
**Acceso:** Público. Abrir sesión; entrega cookie PHPSESSID; bloqueo tras tres intentos fallidos.
**Petición:**
```http
POST /api/auth/login
Content-Type: application/json
{"email": "estudiante@example.test","password": "ClaveSegura1!"}
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 16,"nombre_completo": "Estudiante Demo","email": "estudiante@example.test","rol": "estudiante"}}
```
**Errores:** 401 credenciales; 403 cuenta deshabilitada.

### `POST /api/auth/logout`
**Acceso:** Público (si hay sesión, la cierra). Cerrar la sesión PHP y destruir su cookie lógica.
**Petición:**
```http
POST /api/auth/logout  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 204 No Content  —  cuerpo vacío
```
**Errores:** La ruta no exige rol; la ausencia de sesión también puede cerrarse sin contenido.

## Perfil

### `GET /api/perfil`
**Acceso:** Autenticado. Consultar datos públicos de la cuenta propia.
**Petición:**
```http
GET /api/perfil  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 16,"nombre_completo": "Estudiante Demo","email": "estudiante@example.test","genero": "otro","rol": "estudiante","avatar": "default.png"}}
```
**Errores:** 401 sin sesión.

### `PATCH /api/perfil`
**Acceso:** Autenticado. Actualizar uno o varios datos de perfil; permite cambio de contraseña validado.
**Petición:**
```http
PATCH /api/perfil
Content-Type: application/json
{"nombre_completo": "Estudiante Actualizado"}
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 16,"nombre_completo": "Estudiante Actualizado","email": "estudiante@example.test","rol": "estudiante"}}
```
**Errores:** 409 email duplicado; 422 validación.

### `PUT /api/perfil`
**Acceso:** Autenticado. Alias de la actualización parcial de perfil; acepta los mismos campos que PATCH.
**Petición:**
```http
PUT /api/perfil
Content-Type: application/json
{"genero": "otro"}
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 16,"nombre_completo": "Estudiante Actualizado","genero": "otro","rol": "estudiante"}}
```
**Errores:** 409/422.

### `POST /api/perfil/avatar`
**Acceso:** Autenticado. Subir fotografía de perfil (multipart, avatar JPG/PNG/WebP hasta 2 MB).
**Petición:**
```http
POST /api/perfil/avatar
multipart/form-data: avatar = foto.png
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 16,"avatar": "avatar-generado.webp","rol": "estudiante"}}
```
**Errores:** 400/422 archivo inválido.

## Catálogo y categorías

### `GET /api/categorias`
**Acceso:** Público. Obtener categorías disponibles para filtros y formularios.
**Petición:**
```http
GET /api/categorias  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": [{"id": 1,"nombre": "Programación","descripcion": "Cursos de código"}]}
```
**Errores:** 500.

### `POST /api/categorias`
**Acceso:** Administrador. Crear categoría.
**Petición:**
```http
POST /api/categorias
Content-Type: application/json
{"nombre": "Programación","descripcion": "Cursos de código"}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"id": 1,"nombre": "Programación","descripcion": "Cursos de código"}}
```
**Errores:** 409 nombre duplicado; 422 validación.

### `PATCH /api/categorias/{id}`
**Acceso:** Administrador. Editar nombre y/o descripción.
**Petición:**
```http
PATCH /api/categorias/15
Content-Type: application/json
{"nombre": "Desarrollo web"}
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 1,"nombre": "Desarrollo web","descripcion": "Cursos de código"}}
```
**Errores:** 400/404/409/422.

### `DELETE /api/categorias/{id}`
**Acceso:** Administrador. Eliminar categoría no asociada con cursos.
**Petición:**
```http
DELETE /api/categorias/15  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 204 No Content  —  cuerpo vacío
```
**Errores:** 404 no existe; 409 tiene cursos.

### `GET /api/cursos`
**Acceso:** Público. Listar solo cursos activos; buscar y filtrar.
**Petición:**
```http
GET /api/cursos?buscar=JavaScript&categoria=1&activo=true
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"total": 1,"cursos": [{"id": 15,"titulo": "Curso de Mercado Pago QA","precio": 130.0,"activo": true,"instructor": "Instructor Demo","categorias": "Programación"}]}}
```
**Errores:** 400 filtros; activo=false no permitido.

### `GET /api/cursos/{id}`
**Acceso:** Autenticado. Consultar curso; materiales completos solo con matrícula, autoría o administración.
**Petición:**
```http
GET /api/cursos/15  |  Sin cuerpo; id entero positivo en la ruta
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 15,"titulo": "Curso de Mercado Pago QA","precio": 130.0,"activo": true,"acceso_contenido": true,"niveles": [{"id": 3,"titulo": "Nivel 1"}]}}
```
**Errores:** 400 id; 404 curso inexistente/inaccesible.

### `POST /api/cursos`
**Acceso:** Instructor / admin. Crear curso con una o más categorías válidas.
**Petición:**
```http
POST /api/cursos
Content-Type: application/json
{"titulo": "JavaScript básico","descripcion": "Curso introductorio","imagen": "https://example.com/curso.png","precio": 250.0,"categorias": [1]}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"id": 20,"titulo": "JavaScript básico","precio": 250.0,"activo": true}}
```
**Errores:** 403 rol; 422 precio/categorías.

### `PATCH /api/cursos/{id}`
**Acceso:** Propietario / admin. Editar curso o aplicar baja lógica usando activo=false.
**Petición:**
```http
PATCH /api/cursos/15
Content-Type: application/json
{"titulo": "JavaScript desde cero","activo": false}
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 20,"titulo": "JavaScript desde cero","activo": false}}
```
**Errores:** 403 no propietario; 404; 422.

### `POST /api/archivos`
**Acceso:** Instructor / admin. Guardar imagen, PDF o video en carpeta pública controlada.
**Petición:**
```http
POST /api/archivos
multipart/form-data: tipo = video; archivo = clase.mp4
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"url": "/uploads/courses/clase-generada.mp4","tipo": "video"}}
```
**Errores:** 400/422 archivo, tipo o tamaño.

## Niveles y aprendizaje

### `GET /api/cursos/{id}/niveles`
**Acceso:** Autenticado. Listar niveles y revelar recursos solo cuando corresponda.
**Petición:**
```http
GET /api/cursos/15/niveles  |  Sin cuerpo; id = curso
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"acceso_contenido": true,"niveles": [{"id": 3,"titulo": "Introducción","video_url": "https://example.com/clase.mp4","precio": 0.0}]}}
```
**Errores:** 400/404.

### `POST /api/cursos/{id}/niveles`
**Acceso:** Propietario / admin. Crear nivel con video obligatorio; precio cero o positivo.
**Petición:**
```http
POST /api/cursos/15/niveles
Content-Type: application/json
{"titulo": "Unidad 1","descripcion": "Bases","contenido": "Apuntes","video_url": "https://example.com/clase.mp4","precio": 0}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"id": 30,"curso_id": 20}}
```
**Errores:** 403 propiedad; 422 video/precio.

### `PATCH /api/niveles/{id}`
**Acceso:** Propietario / admin. Actualizar uno o más campos de nivel.
**Petición:**
```http
PATCH /api/niveles/15
Content-Type: application/json
{"titulo": "Unidad 1 actualizada"}
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 30,"actualizado": true}}
```
**Errores:** 403/404/422.

### `DELETE /api/niveles/{id}`
**Acceso:** Propietario / admin. Eliminar nivel si no afecta alumnos inscritos.
**Petición:**
```http
DELETE /api/niveles/15  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 204 No Content  —  cuerpo vacío
```
**Errores:** 404; 409 curso con matrículas.

### `POST /api/niveles/{id}/recursos`
**Acceso:** Propietario / admin. Adjuntar enlace, imagen o PDF al nivel.
**Petición:**
```http
POST /api/niveles/15/recursos
Content-Type: application/json
{"tipo": "pdf","titulo": "Guía","url": "https://example.com/guia.pdf"}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"id": 18,"nivel_id": 30}}
```
**Errores:** 403/404/422.

### `DELETE /api/recursos/{id}`
**Acceso:** Propietario / admin. Quitar recurso de aprendizaje.
**Petición:**
```http
DELETE /api/recursos/15  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 204 No Content  —  cuerpo vacío
```
**Errores:** 403/404.

### `POST /api/inscripciones`
**Acceso:** Estudiante. Inscribirse a curso gratuito; cursos con precio requieren compra.
**Petición:**
```http
POST /api/inscripciones
Content-Type: application/json
{"curso_id": 21}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"inscripcion_id": 40,"curso_id": 21,"mensaje": "Inscripción gratuita creada"}}
```
**Errores:** 400 id; 404; 409 curso de pago/duplicado.

### `GET /api/inscripciones`
**Acceso:** Estudiante. Consultar Kardex, progreso y diplomas; filtros opcionales.
**Petición:**
```http
GET /api/inscripciones?fecha_desde=2026-10-01&terminados=false&activos=true&categoria=1
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": [{"inscripcion_id": 40,"curso_id": 21,"titulo": "Curso gratuito","total_niveles": 2,"completados": 1,"porcentaje": 50.0,"certificado_id": null}]}
```
**Errores:** 400 filtros.

### `POST /api/niveles/{id}/completar`
**Acceso:** Estudiante inscrito. Marcar nivel como completado; al terminar todos se emite certificado.
**Petición:**
```http
POST /api/niveles/15/completar  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"inscripcion_id": 40,"niveles_completados": 2,"niveles_totales": 2,"terminado": true,"certificado": {"id": 7,"codigo": "3f59f995-0c5a-43be-a4a3-dfdcc5432e22"}}}
```
**Errores:** 400 id; 403 no inscrito.

### `GET /api/certificados/{id}`
**Acceso:** Alumno / instructor / admin. Consultar certificado emitido; verifica relación con recurso.
**Petición:**
```http
GET /api/certificados/15  |  Sin cuerpo; id = certificado
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 7,"codigo": "3f59f995-0c5a-43be-a4a3-dfdcc5432e22","alumno": "Estudiante Demo","curso": "JavaScript básico","instructor": "Instructor Demo"}}
```
**Errores:** 403 ajeno; 404 inexistente.

## Compras y pagos

### `POST /api/compras`
**Acceso:** Estudiante. Crear compra de curso pagado. Mercado Pago solicita una orden; la transferencia demo simula aprobación.
**Petición:**
```http
POST /api/compras
Content-Type: application/json
{"curso_id": 15,"metodo_pago": "mercado_pago"}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"id_compra": 9,"curso_id": 15,"curso": "Curso de Mercado Pago QA","monto": 130.0,"estado": "pendiente","order_id": "ORDTST-DEMO","checkout_url": "https://www.mercadopago.com.mx/checkout/v1/redirect?...","referencia_externa": "polycraft-demo"}}
```
**Errores:** 400 gratuito; 404; 409 inscrito; 422; 502/503 proveedor.

### `GET /api/compras/{id}`
**Acceso:** Estudiante propietario. Consultar compra propia; si está pendiente, sincroniza estado desde Mercado Pago.
**Petición:**
```http
GET /api/compras/15  |  Sin cuerpo; id = compra
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 9,"curso_id": 15,"monto": 130.0,"estado": "aprobada","mp_status": "processed"}}
```
**Errores:** 400 id; 404 ajena/inexistente; 503 proveedor.

## Comunidad

### `GET /api/cursos/{id}/comentarios`
**Acceso:** Autenticado. Consultar reseñas; la moderación deja una leyenda sustituta.
**Petición:**
```http
GET /api/cursos/15/comentarios  |  Sin cuerpo; id = curso
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": [{"id": 8,"calificacion": 5,"comentario": "Muy buen curso","eliminado": false}]}
```
**Errores:** 400/404.

### `POST /api/cursos/{id}/comentarios`
**Acceso:** Estudiante graduado. Publicar una reseña de 1 a 5 estrellas al completar el curso.
**Petición:**
```http
POST /api/cursos/15/comentarios
Content-Type: application/json
{"calificacion": 5,"comentario": "Muy buen curso"}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"id": 8,"curso_id": 15,"calificacion": 5}}
```
**Errores:** 403 no completó; 409 duplicado; 422.

### `DELETE /api/comentarios/{id}`
**Acceso:** Administrador. Moderar con causa de 5 a 255 caracteres sin destruir registro.
**Petición:**
```http
DELETE /api/comentarios/15
Content-Type: application/json
{"causa": "Contenido ofensivo"}
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 8,"mensaje": "Comentario retirado; la leyenda sustituta queda visible"}}
```
**Errores:** 404; 409 ya eliminado; 422 causa.

### `GET /api/contactos`
**Acceso:** Autenticado. Listar usuarios elegibles para chat.
**Petición:**
```http
GET /api/contactos  |  Sin cuerpo
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": [{"id": 2,"nombre_completo": "Instructor Demo","rol": "instructor"}]}
```
**Errores:** 401.

### `GET /api/mensajes`
**Acceso:** Autenticado. Consultar conversación privada con el usuario indicado.
**Petición:**
```http
GET /api/mensajes?con=2
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": [{"id": 5,"contenido": "Hola","fecha_hora": "2026-11-21 09:30:00"}]}
```
**Errores:** 400 id; 404 contacto.

### `POST /api/mensajes`
**Acceso:** Autenticado. Enviar mensaje privado; curso_id es opcional.
**Petición:**
```http
POST /api/mensajes
Content-Type: application/json
{"destinatario_id": 2,"contenido": "Hola, instructor","curso_id": 15}
```
**Respuesta de éxito:**
```json
HTTP 201
{"ok": true,"data": {"id": 6,"mensaje": "Mensaje enviado"}}
```
**Errores:** 400/404 destinatario/curso; 422 texto.

## Reportes y administración

### `GET /api/ventas`
**Acceso:** Instructor. Consultar ventas propias con filtros de fecha, categoría y activo.
**Petición:**
```http
GET /api/ventas?fecha_desde=2026-10-01&activos=true&categoria=1
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"cursos": [{"id": 15,"titulo": "Curso de Mercado Pago QA","alumnos": 1,"ingresos": 130.0}],"total_ingresos": 130.0,"por_forma_pago": [{"forma_pago": "Mercado Pago","total": 130.0}]}}
```
**Errores:** 400 filtros; 403 rol.

### `GET /api/ventas/{id}`
**Acceso:** Instructor propietario. Consultar desglose de ventas del curso propio.
**Petición:**
```http
GET /api/ventas/15  |  Sin cuerpo; id = curso
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"curso": {"id": 15,"titulo": "Curso de Mercado Pago QA"},"alumnos": [{"inscripcion_id": 40,"alumno": "Estudiante Demo","pagado": 130.0}],"total_ingresos": 130.0}}
```
**Errores:** 400 id; 404 ajeno/inexistente.

### `GET /api/reportes/usuarios`
**Acceso:** Administrador. Reporte agrupado por tipo de cuenta.
**Petición:**
```http
GET /api/reportes/usuarios?tipo=estudiante
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"tipo": "estudiante","usuarios": [{"id": 16,"nombre": "Estudiante Demo","cursos_inscritos": 1,"cursos_terminados": 0}]}}
```
**Errores:** 400 tipo; 403 rol.

### `GET /api/usuarios`
**Acceso:** Administrador. Consultar usuarios con paginación y filtro de rol.
**Petición:**
```http
GET /api/usuarios?rol=estudiante&pagina=1&limite=50
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": [{"id": 16,"rol": "estudiante","activo": true}]}
```
**Errores:** 403 rol; 422 filtro.

### `PATCH /api/usuarios/{id}/estado`
**Acceso:** Administrador. Bloquear o reactivar usuario; reinicia intentos al reactivar.
**Petición:**
```http
PATCH /api/usuarios/15/estado
Content-Type: application/json
{"activo": true}
```
**Respuesta de éxito:**
```json
HTTP 200
{"ok": true,"data": {"id": 16,"rol": "estudiante","activo": true}}
```
**Errores:** 404 no existe; 409 propia cuenta; 422.

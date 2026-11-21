# Polycraft · Contrato REST y casos de prueba — Tercera entrega

Versión de la documentación: 21/11/2026. Base: `http://localhost/Polycraft-main`. El front consume las rutas mediante `fetch`; Postman usa la misma API. Arquitectura: PHP POO/MVC, MySQL en modelos, sesiones PHP, middleware de roles y factoría de inyección de dependencias.

## Convenciones y seguridad

- Peticiones de datos: `Content-Type: application/json`; archivos y avatar: `multipart/form-data`.
- Sesión: cookie `PHPSESSID` establecida mediante `POST /api/auth/login`; Postman guarda cookies por host. No se pasan secretos en query ni URL.
- Respuesta de éxito: `{"ok":true,"data":{...}}`; en listas `data` puede ser un arreglo. Alta: HTTP 201; eliminación exitosa: HTTP 204 sin cuerpo.
- Error: `{"ok":false,"mensaje":"Descripción controlada"}` (a veces añade `errores`). HTTP 400 entrada inválida, 401 sin sesión, 403 rol/propiedad, 404 inexistente, 409 conflicto, 422 campos inválidos, 500 inesperado y 503 proveedor/configuración.
- Roles: P público; U cualquier cuenta activa; I instructor propietario o administrador para cursos; E estudiante; A administrador; V solo instructor propietario para ventas. `GET /api/cursos/{id}` requiere U.
- Parámetros `{id}` enteros positivos, obtenidos de las respuestas y enlaces del portal. Fechas ISO en filtros `YYYY-MM-DD`. Los precios se almacenan en MXN. Campo `activo=false` da de baja sin eliminar historial.
- Contraseñas protegidas con `password_hash/password_verify`; credenciales MySQL/Mercado Pago solo en `config/local.php`, no en Postman ni en Git.

## Endpoints: ruta, rol, entrada y ejemplo de respuesta

Los ejemplos de éxito se presentan como el valor de `data` de la envoltura JSON, salvo HTTP 204. Los valores son demostrativos; los IDs reales los genera MySQL.

| Método y ruta | Rol | Entrada de ejemplo (JSON o query) | Éxito: código y `data` de ejemplo |
|---|---|---|---|
| GET /api/health | P | Sin entrada | 200 `{"servicio":"Polycraft API","version":"1.0"}` |
| POST /api/auth/registro | P | `{"rol":"estudiante","nombre_completo":"Ana Demo","email":"ana@example.test","genero":"otro","fecha_nacimiento":"2000-01-01","password":"ClaveSegura1!"}` | 201 `{"id":5,"rol":"estudiante",...}` |
| POST /api/auth/login | P | `{"email":"ana@example.test","password":"ClaveSegura1!"}` | 200 `{"id":5,"rol":"estudiante",...}` y cookie |
| POST /api/auth/logout | U | Sin entrada | 200 `{"mensaje":"Sesión cerrada"}` |
| GET /api/perfil | U | Sin entrada | 200 `{"id":5,"nombre_completo":"Ana Demo",...}` |
| PATCH /api/perfil | U | `{"nombre_completo":"Ana Actualizada"}`; contraseña: `password_actual` + `password_nueva` | 200 perfil actualizado |
| PUT /api/perfil | U | Mismo JSON que PATCH | 200 perfil actualizado |
| POST /api/perfil/avatar | U | Multipart `avatar` JPG/PNG/WebP ≤2 MB | 200 perfil con nuevo `avatar` |
| GET /api/categorias | P | Sin entrada | 200 `[{"id":1,"nombre":"Programación",...}]` |
| POST /api/categorias | A | `{"nombre":"Programación","descripcion":"Cursos de código"}` | 201 `{"id":1,"nombre":"Programación",...}` |
| PATCH /api/categorias/{id} | A | `{"nombre":"Desarrollo"}` | 200 categoría actualizada |
| DELETE /api/categorias/{id} | A | Sin entrada; 409 si asociada a cursos | 204 sin cuerpo |
| GET /api/cursos | P | `?buscar=JavaScript&categoria=1&activo=true`; admite instructor/fechas | 200 `{"total":1,"cursos":[{"id":1,"titulo":"JavaScript",...}]}` |
| GET /api/cursos/{id} | U | ID en ruta | 200 curso con `niveles` y `acceso_contenido` (material restringido si no inscrito) |
| POST /api/cursos | I | `{"titulo":"JavaScript","descripcion":"Introducción","precio":250,"imagen":"https://example.com/curso.png","categorias":[1]}` | 201 curso con ID |
| PATCH /api/cursos/{id} | I | `{"titulo":"JavaScript 2","activo":false}` | 200 curso actualizado |
| POST /api/archivos | I | Multipart `tipo=imagen|pdf|video`, `archivo=<binario>` | 201 `{"url":"/uploads/courses/archivo.png","tipo":"imagen"}` |
| GET /api/cursos/{id}/niveles | U | ID del curso | 200 `{"acceso_contenido":false,"niveles":[...]}` |
| POST /api/cursos/{id}/niveles | I | `{"titulo":"Unidad 1","descripcion":"Bases","contenido":"Apuntes","video_url":"https://example.com/clase.mp4","precio":0}` | 201 `{"id":3,"curso_id":1}` |
| PATCH /api/niveles/{id} | I | `{"titulo":"Unidad 1 actualizada"}` | 200 `{"id":3,"actualizado":true}` |
| DELETE /api/niveles/{id} | I | Sin entrada; 409 con alumnos inscritos | 204 sin cuerpo |
| POST /api/niveles/{id}/recursos | I | `{"tipo":"pdf","titulo":"Guía","url":"https://example.com/guia.pdf"}` | 201 `{"id":2,"nivel_id":3}` |
| DELETE /api/recursos/{id} | I | Sin entrada | 204 sin cuerpo |
| POST /api/inscripciones | E | `{"curso_id":2}` (solo cursos gratuitos) | 201 `{"inscripcion_id":2,"curso_id":2,...}` |
| GET /api/inscripciones | E | `?fecha_desde=2026-10-01&terminados=false&activos=true&categoria=1` | 200 lista Kardex con progreso y `certificado_id` |
| POST /api/niveles/{id}/completar | E | Sin entrada; nivel de curso inscrito | 200 progreso actualizado; al completar todo genera certificado |
| GET /api/certificados/{id} | U* | ID; solo alumno, su instructor o administrador | 200 `{"id":4,"alumno":"Ana Demo","curso":"JavaScript","instructor":"Instructor Demo",...}` |
| POST /api/compras | E | `{"curso_id":1,"metodo_pago":"transferencia_demo"}` o `"mercado_pago"` | 201 `{"id_compra":2,"estado":"aprobada","metodo_pago":"transferencia_demo",...}` o `{"estado":"pendiente","checkout_url":"https://...",...}` |
| GET /api/compras/{id} | E* | Compra propia; si MP pendiente consulta al proveedor solo con flag de prueba | 200 `{"id":2,"estado":"aprobada",...}` |
| GET /api/cursos/{id}/comentarios | U | ID curso | 200 comentarios visibles, calificaciones y texto sustituto si moderado |
| POST /api/cursos/{id}/comentarios | E* | `{"calificacion":5,"comentario":"Excelente material"}`; solo después de terminar | 201 `{"id":2,"curso_id":1,"calificacion":5}` |
| DELETE /api/comentarios/{id} | A | `{"causa":"Contenido ofensivo"}` | 200 `{"id":2,"mensaje":"Comentario retirado; ..."}` |
| GET /api/contactos | U | Sin entrada | 200 lista de usuarios para chat |
| GET /api/mensajes | U | `?con=2` | 200 conversación privada de ambos usuarios |
| POST /api/mensajes | U | `{"destinatario_id":2,"contenido":"Hola","curso_id":1}` (`curso_id` opcional) | 201 `{"id":5,"mensaje":"Mensaje enviado"}` |
| GET /api/ventas | V | `?fecha_desde=2026-10-01&categoria=1&activos=true` | 200 resumen de cursos, alumnos, ingresos, formas de pago |
| GET /api/ventas/{id} | V* | ID curso propio | 200 detalle por estudiante/inscripción/pago |
| GET /api/reportes/usuarios | A | `?tipo=estudiante` o `tipo=instructor` | 200 `{"tipo":"estudiante","usuarios":[...]}` |
| GET /api/usuarios | A | `?rol=estudiante&pagina=1&limite=50` | 200 lista de usuarios con estado |
| PATCH /api/usuarios/{id}/estado | A | `{"activo":false}` o `{"activo":true}` | 200 usuario actualizado |

*Acceso adicional restringido por propiedad, matrícula o relación con el recurso en el controlador.

## Flujo concreto de extremo a extremo

1. Admin: iniciar sesión; `POST /api/categorias`; crear al menos una categoría.
2. Instructor: registro/login; `POST /api/cursos` con `categorias:[id]`; `POST /api/cursos/{id}/niveles` con `video_url`; opcional `POST /api/archivos` y `POST /api/niveles/{id}/recursos`.
3. Estudiante: registro/login; `GET /api/cursos`; si gratuito, `POST /api/inscripciones`; si tiene costo, `POST /api/compras` con `transferencia_demo` (simula aprobación sin dinero) o `mercado_pago` exclusivamente en ambiente de prueba confirmado.
4. Estudiante: `GET /api/cursos/{id}`, `POST /api/niveles/{id}/completar`; `GET /api/inscripciones`; `GET /api/certificados/{id}` y página imprimible `/certificado?id=...`.
5. Estudiante graduado: `POST /api/cursos/{id}/comentarios`; instructor recibe chat y revisa `/ventas`; admin revisa `/reportes`, modera y bloquea si procede.
6. Baja lógica: instructor envía `PATCH /api/cursos/{id}` con `{"activo":false}`; desaparece del catálogo público pero no se borra historial de alumnos y pagos.

## Pruebas positivas y negativas documentadas

| Caso | Resultado esperado |
|---|---|
| `GET /api/health`; `GET /api/cursos` | 200 |
| Registro/login válido | 201 / 200 |
| Registro como administrador público | 422 |
| `POST /api/cursos` sin sesión | 401 |
| `POST /api/cursos` como estudiante | 403 |
| Curso con precio -1 | 422 |
| ID inválido `/api/cursos/abc` | 400 |
| Curso inexistente | 404 |
| Inscripción a curso de pago sin comprar | 409 |
| Comentario sin completar curso | 403 |
| Certificado de otra persona | 403 |
| Modificar curso ajeno | 403 |
| Categoría vinculada a cursos no borrable | 409 |
| Origen CORS no permitido | 403 |
| Preflight permitido | 204 |
| Archivo multipart PNG autorizado | 201 |
| Alta y consulta de curso, nivel y recurso | 201 / 200 |
| Transferencia simulada, acceso y finalización | 201 / 200 |
| Conversación privada y reporte ventas | 201 / 200 |

**Evidencia ejecutada**: `php scripts/probar_avance2.php`, 60 pruebas correctas y cero fallidas el 21/11/2026 contra Apache/MySQL local; incluye peticiones HTTP reales y carga multipart PNG. `node scripts/revisar_js.js`: 13 scripts válidos, 0 errores. PHP lint: 61 archivos, 0 errores; prueba de inyección con FakeAcademia: OK. **No** se ejecutó checkout de Mercado Pago en este ciclo; no presentar estos 60 casos como una corrida de Postman.

## Postman

Archivo: `postman/Polycraft-Avance3.postman_collection.json` (60 solicitudes). En Postman usa **Import > File**, selecciona la nueva colección, configura `baseUrl` y las variables demo, y ejecuta carpetas por orden. El administrador requiere contraseña en variable local (no compartas el secreto). La colección captura IDs de respuestas. `runMpSandbox=false` mantiene el pago externo excluido por defecto. Si la misma cuenta demo ya existe, el registro devuelve conflicto; inicia sesión en vez de registrarla de nuevo.

## Mercado Pago: entorno exclusivamente de prueba

`config/local.php` contiene `MP_ACCESS_TOKEN` del servidor. Por seguridad, `MP_HABILITAR_CHECKOUT_PRUEBA` permanece `0` hasta que se confirme **desde el panel del proveedor** que la integración y cuentas/tarjetas son de prueba. Con `1` se habilita llamada a Orders API y verificación de órdenes pendientes. `transferencia_demo` es un **simulador académico**, no una transferencia bancaria real; registra una aprobación simulada en MySQL e inscribe. Nunca usar token de producción para la demostración, ni confiar en que el prefijo del token diferencia entornos.

## Limitaciones que deben declararse al entregar

- El historial Git verificable y aportaciones de ambos integrantes no se pueden reconstruir legítimamente si la carpeta no tiene repositorio `.git`; hay que aportar el histórico real.
- No se ha verificado este ciclo un checkout externo exitoso de Mercado Pago ni un resultado de Postman Runner; el test automatizado fue desde PHP/cURL.
- No hay hosting externo (opcional en la rúbrica). La experiencia está preparada para Apache/MySQL en localhost.
- La segunda forma es deliberadamente una simulación sin dinero; jamás se debe presentar como operación bancaria real.

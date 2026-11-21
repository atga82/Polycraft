# Polycraft — cotejo de la rúbrica del tercer avance (21/11/2026)

Este cotejo se construyó contra el PDF `FE-PWCI-051-FAIA-AD2026`, tercera entrega de 100 puntos y Anexo 1. **No implica calificación oficial:** distingue implementación, verificación automatizada y evidencia pendiente de demostrar.

| Criterio de la rúbrica | Máx. | Evidencia del proyecto | Estado del cotejo |
|---|---:|---|---|
| Cursos, categorías, niveles, recursos, inscripciones, progreso y baja lógica | 16 | `/crear-curso`, `/curso?id=N`, `AcademiaController.php`, `CursoController.php`, DB; prueba QA | Implementado, API probada; recorrer manualmente los formularios |
| API propia en flujo real y Postman | 12 | `app/routes.php`, `fetch` en vistas, 60 casos PHP/cURL, colección de 60 requests | HTTP comprobado; **falta Runner de Postman** |
| Kardex, ventas (resumen y detalle), usuarios/filtros/formatos | 10 | `/kardex`, `/ventas`, `/reportes`, `Reporte.php`, `portal-avance3.js` | Implementado; ventas/Kardex API probadas; panel admin manual pendiente |
| Inyección de dependencias sustituible | 10 | `Container.php`, `AcademiaRepositoryInterface.php`, factoría de `public/index.php`, constructor `AcademiaController` | Implementado en código |
| Gestión admin, bloqueo y moderación con causa/leyenda | 8 | `/reportes`, `CategoriaController`, `UsuarioController`, `SocialController` | Implementado; permisos negativos probados; recorrido admin pendiente |
| Reseñas/promedio, chat con foto/hora, filtros de catálogo | 8 | `/curso?id=N`, `/chat`, `Social.php`, `Menu.php` | Implementado; casos API de comentarios y chat probados |
| Compras con dos formas de pago y certificado automático | 8 | `CompraController` (transferencia simulada + MP Orders), `Academia::completarNivel`, `/certificado` | Transferencia y certificado probados; MP externo **sin prueba** |
| Seguridad (validación, roles, hash, secretos locales) | 8 | `Validator`, middlewares, `Usuario`, `config/local.php.example` | Revisado, negativos 401/403/422 y CORS probados; no auditoría de penetración |
| Avisos de guardar, borrar y error | 6 | Formularios `fetch`, `confirm`, mensajes `role=status` | Implementado; revisión manual de UX pendiente |
| Contrato con ejemplos por ruta y documento Word | 6 | `docs/CONTRATO_API_AVANCE3.md`, `docs/MANUAL_TERCERA_ENTREGA.docx` | Archivos creados y DOCX válido |
| Git distribuido entre ambos integrantes | 4 | `git status` indica que esta carpeta no es un repositorio | **No verificable / pendiente de aportar histórico auténtico** |
| Hosting | Extra opcional | Localhost XAMPP | No realizado |

## Puntos del Anexo 1

- PHP puro / POO / MVC y un Front Controller: `public/index.php`, `app/Core`, `app/Controllers`, `app/Models`, `app/Views`.
- Capa datos aislada: PDO/MySQL dentro de modelos, no SQL en vistas ni controladores.
- HTTP semántico y errores: GET/POST/PATCH/PUT/DELETE, MIME JSON, 200/201/204/400/401/403/404/409/422/500/503.
- Rutas de recurso sin verbos: `/api/cursos`, `/api/niveles`, `/api/inscripciones`, `/api/mensajes`, etc.
- API testable de forma independiente: `postman/Polycraft-Avance3.postman_collection.json` y `scripts/probar_avance2.php`.
- Validación cliente y servidor: PHP en controladores, JavaScript en formularios.
- Control de sesión/rol, CORS explícito y secretos fuera de archivos públicos.
- Dependencias recibidas en constructor con interfaz, container para resolver controlador. Prueba `php scripts/probar_inyeccion.php` ejecutada sin BD: OK.
- SQL único de instalación limpia: `database/portal_cursos.sql`; no sobrescribir BD actual.
- Manual Word implementado y verificado estructuralmente: `docs/MANUAL_TERCERA_ENTREGA.docx`.

## Ejecutado en Desktop Commander

1. `php scripts/probar_avance2.php`: **60/60 OK**, incluidos 201 multipart PNG, creación de curso/nivel, pago demo aprobado, inscripción, progreso, certificado, comentarios, chat, reporte de ventas, permisos CORS y páginas 200. No se hicieron órdenes a Mercado Pago.
2. Validación `php -l` en **61 archivos PHP, 0 fallos**.
3. `node scripts/revisar_js.js`: **13 scripts verificados, 0 fallos** tras los cambios de UI.
4. `node scripts/validar_postman.js`: **60 solicitudes, 60 con tests, 0 URLs inválidas**, colección JSON parseada.
5. MySQL existente conservado; no se ejecutó SQL de creación ni se alteraron credenciales de `config/local.php`.

## Antes de entregar al docente

- Ejecutar Postman con una sesión administrador real y guardar capturas de casos positivos y negativos. La colección no está autoimportada al Postman del usuario.
- Confirmar en panel Mercado Pago **entorno Prueba**, establecer `MP_HABILITAR_CHECKOUT_PRUEBA='1'` solo en `config/local.php`, comprar con cuentas/tarjetas **de prueba** y verificar `GET /api/compras/{id}`; conservar evidencia de operación sin dinero. Si no hay certeza del entorno, mantenerlo deshabilitado.
- Revisar presencialmente en navegador el diseño/operación de cada rol (especialmente subidas grandes, admin, moderación y diplomas).
- Aportar repositorio Git real con historial de contribuciones de ambos integrantes y coordinar demostración final. No se inventaron commits históricos.
- Aclarar al docente que la segunda modalidad es una transferencia **simulada**, no bancaria real; y que se eligió compra por curso completo (rúbrica: cursos **o** niveles). El hospedaje adicional es opcional.

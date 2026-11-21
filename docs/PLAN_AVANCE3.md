# Polycraft — Plan de trabajo para la tercera entrega (PDF FE-PWCI-051-FAIA-AD2026)

Revisión: 21 de noviembre de 2026. Carpeta local: C:\xampp\htdocs\Polycraft-main.

## Estado inicial comprobado
- API propia, MySQL, MVC, sesiones/roles, CORS y compras con Mercado Pago: código existente; 39/39 pruebas de integración locales de avance 2.
- La base de datos en uso tenía 1 administrador, 1 categoría y ningún curso/compra/inscripción antes de las pruebas QA.
- Tablas para niveles, recursos, progreso, certificados, comentarios, mensajes y reportes: **existen**, pero faltan las rutas y vistas funcionales.
- Formularios de crear curso, Kardex, Ventas, Reportes y Chat: prototipos HTML, sin integración real.
- No se verificó una compra nueva en Mercado Pago en este ciclo. El prefijo del token no identifica de forma inequívoca el entorno de prueba.
- Puntos no solucionables solo con código: visto bueno del profesor, historial Git con participación de ambos autores, alojamiento opcional, demostración presencial.

## Plan — módulos (en este orden)
1. **Datos, integridad y seguridad.** Preservar la BD actual; agregar migración incremental solo si se necesitan columnas/índices. Revisar acceso y roles, validación de campos, CSRF, mensajes de error. Mantener token fuera de Git.
2. **DI (requisito 10 puntos).** Introducir interfaces/inyección de servicios en controladores y una factoría/contenedor mínimo en el router; verificar sustitución por implementación de prueba.
3. **Cursos, niveles y recursos (16 puntos).** CRUD de niveles con video obligatorio (URL o archivo validado), contenido textual y enlaces/adjuntos; dar de baja curso lógicamente; inscripción gratuita y acceso autorizado.
4. **Progreso y certificados (16+8 puntos).** Marcar niveles completados, guardar fechas y progreso; finalizar inscripción cuando complete todos, generar automáticamente certificado con alumno, curso, instructor y fecha.
5. **Pago y acceso (8 puntos).** Conservar Mercado Pago Orders sandbox y conciliación sin confiar en el navegador. Añadir una segunda modalidad **solo de demostración** claramente identificada. No ejecutar pagos reales; verificar el entorno Prueba antes de efectuar checkout.
6. **Comentarios y mensajería (8 puntos).** Solo estudiantes que terminaron pueden calificar; calcular promedio; moderación con causa y leyenda de eliminado; chat privado seguro con fecha, usuario y avatar.
7. **Administración (8 puntos).** CRUD de categorías (respetando cursos asociados), bloqueo/desbloqueo, moderación y reporte de usuarios activos.
8. **Reportes (10 puntos).** Kardex filtrable por inscripción/categoría/estado/curso activo; Ventas por curso y detalle por alumno, con totales por forma de pago; reportes instructor y estudiante; moneda MXN y fecha d-M-Y.
9. **Cliente y usabilidad (12+6 puntos).** Convertir vistas en interfaces reales fetch, mostrar validación, confirmaciones de baja, estados de carga y errores; navegación por rol y seguridad.
10. **Evidencia y entrega (6+4 puntos).** Ampliar documentación por endpoint, completar Postman con respuestas éxito/error, pruebas automáticas, script SQL único, manual de instalación/operación en Word; verificar Git sin crear commits ajenos.

## Criterios de aceptación
- API devuelve JSON y estados HTTP correctos, sin fugas de SQL, tokens ni rutas internas.
- Todos los endpoints protegidos requieren sesión y rol; no se permite capturar IDs manualmente para navegación general.
- Cursos con categorías; todos los niveles deben tener video; compras autorizan acceso; baja lógica no borra historial.
- Progreso consistente y certificado idempotente al completar niveles.
- Pruebas automatizadas + pruebas en navegador por rol y colección Postman desde el equipo.
- Se documenta por separado lo pendiente o no verificable (Mercado Pago, Git, hosting).

## Registro de ejecución y estado al 21/11/2026

| Etapa | Estado ejecutado / evidencia |
|---|---|
| 1. Datos, seguridad | Esquema MySQL original suficiente, no se alteró ni reinició la BD. Sesiones/roles, validación del lado servidor, CORS y archivos controlados. 61 archivos PHP sin errores. |
| 2. Inyección de dependencias | `Container` en Router con factoría registrada; `AcademiaRepositoryInterface` inyectada al constructor de `AcademiaController`. Sustitución efectiva comprobada con `php scripts/probar_inyeccion.php`: FakeAcademia sin MySQL. |
| 3. Cursos, niveles, recursos | API CRUD con video y PDF, subida multipart, formulario de alta y panel visual de edición/alta posterior/baja lógica de cursos. Prueba real de PNG 201. |
| 4. Inscripción/progreso | Matrícula gratuita, progreso idempotente, certificado automático y vista de diploma imprimible. Probado en API. |
| 5. Compras | Transferencia simulada aprobada sin mover dinero, probada de extremo a extremo. Mercado Pago existente con resguardo preventivo, no probado externamente este ciclo. |
| 6. Social | Comentario tras terminar curso, promedio, moderación con causa, chat privado con foto y marca de fecha/hora. API probada en roles instructor/estudiante. |
| 7. Administración | CRUD categorías, listado/bloqueo usuarios y moderación desde panel; rutas limitadas a admin. Pruebas negativas 401/403. Sin credencial admin suministrada a esta revisión, faltó recorrido positivo completo del rol. |
| 8. Reportes | Kardex por fechas/categorías/terminados/activos, ventas resumen/detalle, reportes de usuario, formato MXN y fechas. Consultas del instructor probadas. |
| 9. Cliente | Vistas funcionales con fetch, carga/mensajes/confirmaciones; rutas de subida locales corregidas para el subdirectorio `/Polycraft-main`; 13 scripts JavaScript válidos. |
| 10. Entrega | 60 casos API HTTP/cURL correctos y 0 fallidos; colección Postman 60 solicitudes, todas con URL y tests y sin errores estructurales (`node scripts/validar_postman.js`); contrato por endpoint, README y manual Word creados. |

**Pruebas verificadas:** `php scripts/probar_avance2.php`: **60 OK / 0 fallidas**, sin peticiones a Mercado Pago. `node scripts/revisar_js.js`: **13 scripts válidos / 0 errores**. `node scripts/validar_postman.js`: **60 solicitudes / 60 con tests / 0 errores**. `php -l` sobre **61 PHP / 0 errores**.

**Pendientes honestos y externos a la revisión:**

1. Importar `postman/Polycraft-Avance3.postman_collection.json` en el Postman del usuario y ejecutar sus carpetas/guardar capturas del Runner. Los resultados HTTP/cURL son una evidencia equivalente de operación, pero no una captura de Postman.
2. Comprobar visualmente que la app y credenciales de Mercado Pago sean de **entorno de prueba** y, solo entonces, habilitar `MP_HABILITAR_CHECKOUT_PRUEBA='1'` en `config/local.php` para ejecutar y fotografiar un pago sandbox. Las 60 pruebas no incluyen checkout externo.
3. Demostrar el panel admin en una sesión auténtica con contraseña privada de su dueño. Los endpoints de admin fueron revisados en código y los negativos por rol sí probados.
4. Aportar repositorio Git **real** con commits anteriores y participación distribuida de ambos autores. La carpeta inspeccionada no contiene `.git`; no se deben inventar commits pasados.
5. Si se desean puntos extra, alojar proyecto en hosting; actualmente solo corre en localhost. Se requiere la presentación/demostración final del equipo ante el docente.

**Observación funcional:** Se eligió compra por **curso completo** para cumplir la alternativa "curso o nivel" de la rúbrica; los niveles individuales tienen precio y pueden ser gratuitos, pero no se incorporó compra aislada de nivel. La segunda forma de pago es explícitamente simulada para fines académicos. No declarar integración externa validada sin la prueba del proveedor.

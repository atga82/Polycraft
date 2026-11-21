# Revisión de los ZIP entregados

## Comparación de versiones

Se revisaron `Polycraft-paso4.zip`, `Polycraft-paso4-actualizado.zip` y la rúbrica del segundo avance del PDF `FE-PWCI-051-FAIA-AD2026`.

El ZIP que generó Gemini añadía `CursoController.php`, `Models/Curso.php`, `Services/MercadoPagoService.php` y cambios en `routes.php`, pero el servicio de Mercado Pago devolvía un identificador ficticio y una URL de ejemplo en lugar de solicitar una orden real. Además, el modelo nuevo debía cotejarse con las columnas reales de `portal_cursos.sql`, el cliente todavía mostraba datos estáticos en el catálogo y el ZIP actualizado no incluía todos los archivos auxiliares presentes en el original.

La versión actual parte del ZIP original para conservar los archivos existentes y corrige/agrega lo siguiente:

- Implementación de catálogo REST con filtros, detalle, creación y modificación/baja lógica de cursos, utilizando las tablas reales del esquema.
- Endpoints de categorías para poder asociar una categoría existente y respetar que su creación está protegida para administrador.
- Flujo inicial de compras con una orden real de Mercado Pago Checkout Pro vía Orders API, usando el token privado del lado servidor, la referencia externa y una clave de idempotencia.
- Consulta del estado de orden desde el servidor y actualización idempotente de compra, inscripción y pago cuando Mercado Pago confirme el estado acreditado.
- Catálogo y detalle consumidos con `fetch`, manejo visible de errores y login por API.
- Configuración local fuera de Git, CORS explícito, SQL actualizado, migración separada y colección Postman.
- Restauración/conservación de `.htaccess`, avatar predeterminado, `.gitignore` y otros recursos del original.

## Resultados de verificación realizados en este entorno

- Todos los archivos PHP pasaron `php -l`.
- Se importó y validó la estructura JSON de la colección Postman.
- Se inició el servidor PHP integrado y se verificaron respuestas JSON de `/api/health` (200), recurso inexistente (404), filtro público inválido (400), rutas protegidas de creación y detalle sin sesión (401), CORS con origen no autorizado (403) y preflight permitido (204). El controlador valida el formato del ID de curso después de que el middleware autentica al usuario; ese caso debe probarse de nuevo con una sesión válida.
- Se revisó la sintaxis JavaScript de las vistas de catálogo y login.

## Límites que deben quedar claros

El entorno de validación no tiene el controlador PDO de MySQL (`pdo_mysql`) ni cURL instalados, y no se proporcionó una credencial de prueba de Mercado Pago. Por ello no fue posible ejecutar consultas contra una base MySQL real ni crear/consultar una orden de pago desde aquí. La integración está implementada en código, pero debe demostrarse con XAMPP, MySQL y credenciales de prueba del equipo.

El proyecto aún no equivale al portal final: Kardex, ventas, chat y reportes mantienen partes de maqueta; la publicación de niveles y videos, progreso, comentarios, mensajería y certificados siguen pendientes de su integración final. No debe presentarse ese trabajo como terminado para la tercera entrega.

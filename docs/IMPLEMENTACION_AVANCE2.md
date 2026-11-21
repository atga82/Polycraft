# Polycraft - Documentación del segundo avance

## 1. Correspondencia con la rúbrica del segundo avance

| Criterio de la rúbrica | Implementación disponible | Evidencia / comprobación |
|---|---|---|
| API propia funcional, JSON, códigos HTTP y errores | `app/Core/Router.php`, `app/Core/Response.php`, `app/Controllers/CursoController.php`, `app/Models/Curso.php` | Colección Postman: catálogo, detalle, creación, modificación y errores de validación |
| MVC en PHP puro con POO | `app/Controllers/`, `app/Models/`, `app/Views/`, `app/Core/` | Seguir la ruta desde `public/index.php` y `app/routes.php` |
| Registro, login, perfil, contraseña cifrada y bloqueo al tercer intento | `AuthController.php`, `AccesoController.php`, `PerfilController.php`, `Usuario.php`, `Validator.php` | Registro de cuenta, login correcto/incorrecto, `/api/perfil` y reintentos fallidos |
| Middleware de sesión y rol | `app/Middleware/Autenticado.php`, `Rol.php` | Probar `POST /api/cursos` sin sesión (401) y `POST /api/categorias` sin rol administrador (403) |
| API de terceros en servidor | `app/Services/MercadoPagoService.php` y `app/Controllers/CompraController.php` | `POST /api/compras` con token de prueba configurado; respuesta con `checkout_url` real |
| Conexión MySQL mediante modelo | `config/database.php`, `app/Core/Model.php` y modelos concretos | Importar el SQL y probar endpoints de catálogo; no hay SQL dentro de vistas ni controladores |
| Consumo mediante fetch/promesas | `app/Views/cursos/Menu.php`, `Curso.php`, `auth/Login.php` | Catálogo, detalle e inicio de sesión consultan la API sin enviar el formulario de forma tradicional |
| CORS explícito | `config/cors.php`, `app/Middleware/Cors.php` | Probar desde origen permitido y desde otro origen; consultar la sección 4 |
| Colección Postman | `postman/Polycraft-Avance2.postman_collection.json` | Importar en Postman y seguir el orden de ejecución de la sección 5 |

**Estado honesto de esta entrega:** el código ya contiene las rutas y capas necesarias para los módulos documentados, y se verificó la sintaxis de los archivos PHP. La API de Mercado Pago requiere las credenciales locales y no se puede declarar como pago real probado hasta ejecutar una compra de prueba con la cuenta del equipo. Kardex, ventas, chat, reportes, creación de niveles, progreso y certificado aún conservan partes de prototipo y no están completos para la entrega final.

## 2. Arquitectura MVC y recorrido de una petición

1. El navegador o Postman envía una petición a una ruta pública.
2. `public/index.php` carga `app/bootstrap.php` y `app/routes.php`.
3. `App\Core\Router` identifica la ruta con el método HTTP y despacha la petición.
4. Se ejecuta el middleware global `Cors`.
5. En una ruta protegida se ejecuta `Autenticado` y después `Rol`; el orden está declarado por cada ruta.
6. El controlador valida datos y llama a uno o más modelos. El controlador no ejecuta consultas SQL.
7. El modelo usa PDO a través de `App\Core\Model` y `Database::conectar()`.
8. `App\Core\Response` devuelve JSON con el código HTTP y el encabezado `Content-Type: application/json; charset=utf-8`.

### Responsabilidades

- **Vista / cliente:** HTML, CSS, JavaScript y consumo de API con `fetch` y `async/await` (promesas).
- **Capa intermedia:** router, controladores, validaciones de petición, middleware, formato JSON y servicio de Mercado Pago.
- **Modelo / datos:** acceso PDO, validaciones de integridad que requieren MySQL, transacciones y persistencia en las tablas.

### Orden del middleware

`Cors` se registra mediante `middlewareGlobal(Cors::class)`, por lo que se ejecuta antes de cualquier middleware de ruta. En una operación que requiere autenticación, sigue `Autenticado` (valida cookie/sesión y estado activo), luego `Rol` (comprueba permisos) y finalmente el controlador. Los errores de las capas internas se convierten a JSON y pasan de regreso por CORS para conservar los encabezados del origen permitido.

## 3. Endpoints principales de la API propia

La estructura uniforme es:

```json
{
  "ok": true,
  "data": { "...": "..." }
}
```

Los errores tienen el formato:

```json
{
  "ok": false,
  "error": {
    "codigo": 422,
    "mensaje": "Datos inválidos",
    "detalles": { "campo": "Descripción del error" }
  }
}
```

| Método y ruta | Acceso | Uso | Éxito / errores importantes |
|---|---|---|---|
| `GET /api/health` | Público | Verificar que PHP responde | 200, 500 |
| `POST /api/auth/registro` | Público | Registrar estudiante o instructor | 201, 409, 422, 500 |
| `POST /api/auth/login` | Público | Iniciar sesión y establecer cookie | 200, 401, 403, 422, 500 |
| `POST /api/auth/logout` | Público | Cerrar sesión actual | 204 |
| `GET /api/perfil` | Sesión válida | Consultar perfil del usuario actual | 200, 401 |
| `PATCH /api/perfil` | Sesión válida | Modificar perfil | 200, 401, 409, 422 |
| `GET /api/categorias` | Público | Listar categorías | 200, 500 |
| `POST /api/categorias` | Administrador | Crear categoría | 201, 401, 403, 409, 422 |
| `GET /api/cursos` | Público | Listar y filtrar cursos activos | 200, 400, 500 |
| `GET /api/cursos/{id}` | Público | Consultar detalle de un curso activo | 200, 400, 404, 500 |
| `POST /api/cursos` | Instructor o administrador | Crear curso con al menos una categoría | 201, 401, 403, 422, 500 |
| `PATCH /api/cursos/{id}` | Instructor propietario o administrador | Modificar curso o darlo de baja con `activo: false` | 200, 400, 401, 403, 404, 422, 500 |
| `GET /api/usuarios` | Administrador | Listar usuarios activos/inactivos según controlador | 200, 401, 403, 500 |
| `PATCH /api/usuarios/{id}/estado` | Administrador | Bloquear/desbloquear cuenta y reiniciar contador | 200, 401, 403, 404, 422 |
| `POST /api/compras` | Estudiante autenticado | Crear compra local y orden en Mercado Pago | 201, 400, 401, 403, 404, 409, 502/503 |
| `GET /api/compras/{id}` | Estudiante propietario | Consultar estado directamente en Mercado Pago y sincronizar compra | 200, 401, 404, 502/503 |

## 4. CORS y configuración local

CORS está explícitamente definido en `config/cors.php`. Los orígenes de la lista deben ser exactamente `esquema://host[:puerto]`, sin barra al final. La propia aplicación reconoce su mismo origen; otros orígenes deben agregarse deliberadamente. Ejemplos de orígenes para desarrollo local:

- XAMPP en `http://localhost`: ya cubierto por la configuración inicial.
- XAMPP en `http://127.0.0.1`: ya cubierto por la configuración inicial.
- Live Server en `http://localhost:5500`: agrégalo a `origenes` solo si se usará para servir el cliente separado.

Las peticiones que incluyan `Origin` no autorizado reciben 403. No se utiliza `Access-Control-Allow-Origin: *`, porque la sesión se identifica con cookie.

## 5. Pruebas en Postman

Importa el archivo `postman/Polycraft-Avance2.postman_collection.json`. Cambia las variables `baseUrl`, `adminEmail`, `adminPassword`, `instructorEmail`, `instructorPassword`, `studentEmail` y `studentPassword` por datos locales de prueba.

Orden sugerido:

1. `Health` y `Listar categorías`.
2. `Login administrador` y luego `Crear categoría`. Primero crea el administrador con `scripts/crear_admin.php`.
3. `Registrar instructor`, `Login instructor`, `Crear curso`, `Listar cursos` y `Detalle curso`.
4. `Registrar estudiante`, `Login estudiante`.
5. Si `config/local.php` contiene un Access Token de prueba válido, ejecuta `Crear compra` y después `Consultar compra`. La primera respuesta debe contener `checkout_url`; la segunda consulta la orden en Mercado Pago y sincroniza la inscripción cuando el proveedor confirme el pago.

Las cookies de sesión de Postman se guardan por host. Si cambias de usuario durante la colección, ejecuta el login del rol que necesita la siguiente operación.

## 6. Pruebas de error recomendadas

- `POST /api/cursos` sin iniciar sesión: 401.
- `POST /api/categorias` con una sesión de estudiante/instructor: 403.
- `GET /api/cursos/abc`: 400.
- `GET /api/cursos/999999` cuando no existe: 404.
- `POST /api/cursos` con precio negativo o sin categorías: 422.
- `GET /api/cursos?activo=incorrecto` o `activo=false` desde el catálogo público: 400.
- Enviar `Origin: http://sitio-no-autorizado.example`: 403.
- Ejecutar `POST /api/compras` sin `MP_ACCESS_TOKEN`: 503 con un mensaje de configuración, no una orden inventada.

## 7. Mercado Pago: alcance y seguridad

La API se consume desde `MercadoPagoService` en el servidor PHP, no desde JavaScript. El Access Token se toma del entorno mediante `config/local.php` (archivo excluido de Git) y nunca se agrega al repositorio. La orden se crea en `POST https://api.mercadopago.com/v1/orders` con `X-Idempotency-Key`; el monto procede de MySQL, no del cuerpo de la petición enviado por el navegador.

Al consultar `GET /api/compras/{id}`, el servidor pregunta a Mercado Pago por el estado de la orden. Solo cuando la respuesta del proveedor informa estado de pago acreditado se cambia la compra local a aprobada y, dentro de una transacción MySQL, se registra la inscripción y el pago. Para una demostración real se requiere un Access Token de prueba válido. Para despliegue productivo, el equipo debe configurar notificaciones/webhooks firmados y probar todos los estados posibles antes de aceptar pagos reales.

Documentación oficial consultada:

- [Checkout Pro mediante Orders API: crear y configurar una orden](https://www.mercadopago.com.mx/developers/es/docs/checkout-pro-orders/create-order?scope=prod)
- [Referencia oficial de Checkout API: crear order](https://www.mercadopago.com.mx/developers/es/reference/online-payments/checkout-api/create-order/post)
- [Modelo de integración de Checkout API / Orders](https://www.mercadopago.com.mx/developers/es/docs/checkout-api-orders/integration-model)

## 8. Revisión final antes de entregar

- Importar `portal_cursos.sql` en una base vacía. Para una base previa de este mismo proyecto, respaldarla y ejecutar una sola vez `migracion_avance2.sql`; no ejecutar ambos archivos en una misma base.
- Verificar que Apache, MySQL y las extensiones `pdo_mysql`, `mbstring` y `curl` estén activas.
- Probar una sesión de cada rol y guardar capturas de Postman para casos positivos y negativos.
- No incluir `config/local.php`, contraseñas reales ni tokens privados en el ZIP entregado a Git.
- Grabar evidencia del navegador, Postman, middleware y creación/consulta de la orden de prueba de Mercado Pago.

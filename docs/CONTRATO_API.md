# Contrato de la API propia de Polycraft

Base local recomendada: `http://localhost/Polycraft-main`

Todas las respuestas JSON declaran `Content-Type: application/json; charset=utf-8`. Los endpoints protegidos requieren la cookie de sesión `POLYCRAFTSESSID`; Postman conserva la cookie al usar el mismo host. Las peticiones con JSON deben enviar `Content-Type: application/json` y `Accept: application/json`.

## GET /api/cursos

Consulta cursos activos por defecto.

Query params opcionales:
- `buscar`: coincidencia parcial en título, descripción o nombre del instructor.
- `categoria`: nombre o ID de categoría.
- `instructor`: coincidencia parcial en nombre o correo exacto.
- `fecha_desde`, `fecha_hasta`: fechas `YYYY-MM-DD` de publicación.
- `activo`: el catálogo público acepta `true` (por defecto); las bajas lógicas no se exponen en el catálogo. Cualquier otro valor devuelve `400`.

Éxito `200`:

```json
{
  "ok": true,
  "data": {
    "total": 1,
    "cursos": [
      {
        "id": 1,
        "titulo": "Introducción a JavaScript",
        "descripcion": "Fundamentos del lenguaje",
        "imagen": "https://example.com/curso.jpg",
        "precio": 250.0,
        "activo": true,
        "instructor": "Nombre del instructor",
        "categorias": "Programación",
        "promedio_calificacion": 0.0
      }
    ]
  }
}
```

Errores: `400` si `activo` o una fecha tienen un formato inválido; `500` ante un fallo no esperado.

## GET /api/cursos/{id}

Acceso: requiere sesión válida con rol estudiante, instructor o administrador. Obtiene detalle y niveles de un curso activo. `{id}` es el ID obtenido de un resultado de API; no se escribe como campo manual en formularios. Respuestas: `200`, `400` (ID no válido), `401` (sin sesión), `403` (rol no autorizado), `404` (no existe o está dado de baja), `500`. Los niveles siguen siendo contenido preliminar: el control de acceso al material comprado forma parte del módulo final.

## POST /api/cursos

Acceso: sesión válida de instructor o administrador. El instructor que crea el curso queda como propietario. Requiere título, descripción, precio y una categoría existente. `imagen` puede ser una URL HTTP(S) o una ruta local relativa.

Petición `201`:

```json
{
  "titulo": "Introducción a JavaScript",
  "descripcion": "Fundamentos del lenguaje desde cero.",
  "imagen": "https://example.com/curso.jpg",
  "precio": 250.00,
  "categorias": [1]
}
```

Devuelve el curso creado dentro de `data`, con el encabezado HTTP `Location: /api/cursos/{id}`. Errores: `401` sin sesión, `403` rol no autorizado, `422` datos inválidos o categoría inexistente, `500` error interno.

## PATCH /api/cursos/{id}

Acceso: instructor propietario o administrador. Solo cambia los campos enviados. Campos permitidos: `titulo`, `descripcion`, `imagen`, `precio`, `categorias` (arreglo de IDs) y `activo` (booleano). Para baja lógica, enviar `{"activo": false}`. No borra físicamente el registro. Respuestas: `200`, `400`, `401`, `403`, `404`, `422`, `500`.

## GET /api/categorias y POST /api/categorias

`GET` es público y devuelve categorías. `POST` requiere administrador.

Petición de creación:

```json
{
  "nombre": "Programación",
  "descripcion": "Cursos de desarrollo de software."
}
```

Devuelve `201`; puede devolver `401`, `403`, `409` si el nombre ya existe, `422` si los datos son inválidos o `500` ante un error interno.

## POST /api/auth/registro

Crea una cuenta de estudiante o instructor. No permite crear administradores mediante registro público.

```json
{
  "rol": "instructor",
  "nombre_completo": "Nombre de Prueba",
  "email": "instructor@example.com",
  "genero": "otro",
  "fecha_nacimiento": "2000-01-01",
  "password": "EjemploSeguro1!"
}
```

Respuestas: `201`, `409` email duplicado, `422` validación, `500` error interno. La contraseña se guarda usando `password_hash`.

## POST /api/auth/login

```json
{
  "email": "instructor@example.com",
  "password": "EjemploSeguro1!"
}
```

Respuestas: `200` y cookie de sesión, `401` credenciales incorrectas, `403` cuenta deshabilitada tras tres errores consecutivos, `422` cuerpo inválido. Al autenticar correctamente se reinician los intentos fallidos.

## POST /api/compras

Acceso: estudiante autenticado. El servidor obtiene el costo real del curso desde MySQL; ignora cualquier monto enviado por el navegador.

```json
{
  "curso_id": 1
}
```

Respuesta `201` (cuando Mercado Pago esté configurado y acepte crear la orden):

```json
{
  "ok": true,
  "data": {
    "id_compra": 7,
    "curso_id": 1,
    "curso": "Introducción a JavaScript",
    "monto": 250.0,
    "estado": "pendiente",
    "order_id": "ORDTST...",
    "checkout_url": "https://www.mercadopago.com.mx/checkout/...",
    "referencia_externa": "polycraft-..."
  }
}
```

Errores: `400` curso gratis/no requiere checkout, `401` sin sesión, `403` rol incorrecto, `404` curso inexistente/inactivo, `409` el estudiante ya está inscrito, `502` proveedor rechazó la petición o `503` integración sin configuración.

## GET /api/compras/{id}

Acceso: solo el estudiante propietario de la compra. Consulta la orden con el servidor de Mercado Pago; el cliente no puede enviar ni forzar el estado. Cuando el proveedor confirma pago acreditado, el modelo actualiza la compra, crea la inscripción si todavía no existe y registra el pago asociado. Respuestas: `200`, `400`, `401`, `404`, `502`, `503`.

# Polycraft

**Polycraft** es un portal de cursos en línea desarrollado para la materia de Programación Web Capa Intermedia de la UANL.

Permite a los **estudiantes** explorar, comprar y completar cursos; a los **instructores** crear cursos, niveles y materiales; y a los **administradores** gestionar usuarios, categorías, comentarios y reportes.

## Tecnologías

- PHP (POO y arquitectura MVC), HTML, CSS y JavaScript con `fetch`.
- MySQL para la información de usuarios, cursos, inscripciones y pagos.
- API REST propia y Mercado Pago **en entorno de prueba** para simular compras.

## Instalación local

1. Instala **XAMPP** (Apache y PHP 8+) y **MySQL**. Puedes administrar MySQL con Workbench; no necesitas arrancar otro servidor MySQL desde XAMPP si ya está activo.
2. Dentro de `C:\xampp\htdocs`, clona el repositorio:

   ```powershell
   git clone https://github.com/atga82/Polycraft.git Polycraft-main
   ```

3. Inicia Apache y comprueba que MySQL esté funcionando.
4. Si es una instalación nueva, importa `database/portal_cursos.sql` desde MySQL Workbench. **Haz respaldo antes de importar sobre una base existente**.
5. Copia `config/local.php.example` como `config/local.php` y configura las credenciales locales de MySQL. No compartas ese archivo.
6. Abre [http://localhost/Polycraft-main/](http://localhost/Polycraft-main/) y comprueba la API en [http://localhost/Polycraft-main/api/health](http://localhost/Polycraft-main/api/health).
7. Para probar las operaciones REST, importa `postman/Polycraft-Avance3.postman_collection.json` en Postman.

## Pagos de demostración

El portal contempla dos modalidades académicas: **Mercado Pago Sandbox**, que requiere credenciales y tarjetas de prueba configuradas solo en el servidor, y **transferencia simulada**, que no mueve dinero. La integración externa debe permanecer deshabilitada cuando no se esté haciendo una prueba supervisada.

## Documentación

- [Documentación de endpoints de la API](docs/Polycraft_Contrato_API_Final.md).
- [Manual de implementación y operación](docs/Polycraft_Manual_Implementacion_y_Operacion.docx).

## Integrantes

- Miguel Ángel Salazar García
- Ángel Tadeo García Almanza

**Universidad Autónoma de Nuevo León · Facultad de Ciencias Físico Matemáticas · Grupo 051 · Agosto–diciembre de 2026.**

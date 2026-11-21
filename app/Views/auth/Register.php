<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">

</head>
<body>
    <div class="login-container">
        <form class="login-box" action="<?= $base ?>/registro" method="POST" id="register-form"
              enctype="multipart/form-data" novalidate>
            <h1>Registrar Usuario</h1>

            <?php if (!empty($error)): ?>
                <div class="alerta alerta-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
 

            <div class="input-group">
                <h1>Tipo de usuario</h1>
                <select id="tipo" name="tipo">
                 <option value="" disabled <?= empty($anterior['tipo']) ? 'selected' : '' ?>>Selecciona una Opción</option>
                 <option value="instructor" <?= ($anterior['tipo'] ?? '') === 'instructor' ? 'selected' : '' ?>>Instructor</option>
                 <option value="estudiante" <?= ($anterior['tipo'] ?? '') === 'estudiante' ? 'selected' : '' ?>>Estudiante</option>
                </select>
                <span class="error-message" id="error-tipo"><?= e($errores['tipo'] ?? '') ?></span>
            </div>


            <div class="input-group">
                <h1>Nombre Completo</h1>
                <input type="text" id="nombre_completo" name="nombre_completo" placeholder="Ingresa tu nombre completo"
                       value="<?= e($anterior['nombre_completo'] ?? '') ?>">
                <span class="error-message" id="error-nombre_completo"><?= e($errores['nombre_completo'] ?? '') ?></span>
            </div>
 
            <div class="input-group">
                <h1>Email</h1>
                <input type="email" id="email" name="email" placeholder="Ingresa tu email"
                       value="<?= e($anterior['email'] ?? '') ?>">
                <span class="error-message" id="error-email"><?= e($errores['email'] ?? '') ?></span>
            </div>
 
            <div class="input-group">
                <h1>Género</h1>
                <select id="genero" name="genero">
                 <option value="" disabled <?= empty($anterior['genero']) ? 'selected' : '' ?>>Selecciona una Opción</option>
                 <option value="masculino" <?= ($anterior['genero'] ?? '') === 'masculino' ? 'selected' : '' ?>>Masculino</option>
                 <option value="femenino" <?= ($anterior['genero'] ?? '') === 'femenino' ? 'selected' : '' ?>>Femenino</option>
                 <option value="otro" <?= ($anterior['genero'] ?? '') === 'otro' ? 'selected' : '' ?>>Otro</option>
                </select>
                <span class="error-message" id="error-genero"><?= e($errores['genero'] ?? '') ?></span>
            </div>

            <div class="input-group">
                <h1>Fecha de Nacimiento</h1>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento"
                       value="<?= e($anterior['fecha_nacimiento'] ?? '') ?>">
                <span class="error-message" id="error-fecha_nacimiento"><?= e($errores['fecha_nacimiento'] ?? '') ?></span>
            </div>

            <div class="input-group">
                <h1>Fotografía</h1>
                <input type="file" id="foto" name="foto" accept="image/jpeg,image/png,image/webp">
                <span class="error-message" id="error-foto"><?= e($errores['foto'] ?? '') ?></span>
            </div>

            <div class="input-group">
                <h1>Contraseña</h1>
                <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña">
                <span class="error-message" id="error-password"><?= e($errores['password'] ?? '') ?></span>
            </div>

        
            <button type="submit" class="btn-login">Registrar</button>

            <p class= "register-link">
¿Ya tienes cuenta? <a href="<?= $base ?>/login">Inicia Sesión</a>

            </p>

          
        </form>
    </div>

    <script>
        const form = document.getElementById('register-form');

        function mostrarError(idCampo, mensaje) {
            document.getElementById('error-' + idCampo).textContent = mensaje;
        }

        function limpiarErrores() {
            document.querySelectorAll('.error-message').forEach(function (el) {
                el.textContent = '';
            });
        }

        function validarFormulario() {
            limpiarErrores();
            let esValido = true;

            const tipo = document.getElementById('tipo').value;
            if (tipo === '') {
                mostrarError('tipo', 'Selecciona un tipo de usuario.');
                esValido = false;
            }

            const nombreCompleto = document.getElementById('nombre_completo').value.trim();
            if (nombreCompleto.length < 2) {
                mostrarError('nombre_completo', 'El nombre completo es obligatorio (mínimo 2 caracteres).');
                esValido = false;
            }

            const email = document.getElementById('email').value.trim();
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (email === '') {
                mostrarError('email', 'El email es obligatorio.');
                esValido = false;
            } else if (!emailRegex.test(email)) {
                mostrarError('email', 'Ingresa un email válido (ejemplo@dominio.com).');
                esValido = false;
            }

            const genero = document.getElementById('genero').value;
            if (genero === '') {
                mostrarError('genero', 'Selecciona un género.');
                esValido = false;
            }

            const fechaNacimiento = document.getElementById('fecha_nacimiento').value;
            if (fechaNacimiento === '') {
                mostrarError('fecha_nacimiento', 'La fecha de nacimiento es obligatoria.');
                esValido = false;
            }

            const foto = document.getElementById('foto').value;
            if (foto === '') {
                mostrarError('foto', 'Selecciona una fotografía.');
                esValido = false;
            }

            const password = document.getElementById('password').value;
            if (password === '') {
                mostrarError('password', 'La contraseña es obligatoria.');
                esValido = false;
            } else if (password.length < 8) {
                mostrarError('password', 'Debe tener al menos 8 caracteres.');
                esValido = false;
            } else if (!/\p{Lu}/u.test(password)) {
                mostrarError('password', 'Debe incluir al menos una letra mayúscula.');
                esValido = false;
            } else if (!/\p{Nd}/u.test(password)) {
                mostrarError('password', 'Debe incluir al menos un número.');
                esValido = false;
            } else if (!/[^\p{L}\p{N}\s]/u.test(password)) {
                mostrarError('password', 'Debe incluir al menos un carácter especial (# $ % & * ! ? etc).');
                esValido = false;
            }

            return esValido;
        }

        // Validacion rapida en el navegador; la del servidor es la que manda.
        // Si todo esta bien, el formulario se envia normalmente (POST /registro).
        form.addEventListener('submit', function (evento) {
            if (!validarFormulario()) {
                evento.preventDefault();
            }
        });
    </script>
</body>
</html>
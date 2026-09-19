<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="style.css">

</head>
<body>
    <div class="login-container">
        <form class="login-box" action="#" method="POST" id="register-form" novalidate>
            <h1>Registrar Usuario</h1>
 

            <div class="input-group">
                <h1>Tipo de usuario</h1>
                <select id="tipo" name="tipo">
                 <option value="" disabled selected>Selecciona una Opción</option>
                 <option value="instructor">Instructor</option>
                 <option value="estudiante">Estudiante</option>
                </select>
                <span class="error-message" id="error-tipo"></span>
            </div>


            <div class="input-group">
                <h1>Nombre Completo</h1>
                <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario">
                <span class="error-message" id="error-usuario"></span>
            </div>
 
            <div class="input-group">
                <h1>Email</h1>
                <input type="text" id="email" name="email" placeholder="Ingresa tu email">
                <span class="error-message" id="error-email"></span>
            </div>
 
            <div class="input-group">
                <h1>Género</h1>
                <select id="genero" name="genero">
                 <option value="" disabled selected>Selecciona una Opción</option>
                 <option value="masculino">Masculino</option>
                 <option value="femenino">Femenino</option>
                 <option value="otro">Otro</option>
                </select>
                <span class="error-message" id="error-genero"></span>
            </div>

            <div class="input-group">
                <h1>Fecha de Nacimiento</h1>
                <input type="date" id="fecha_nacimiento" name="fecha_nacimiento">
                <span class="error-message" id="error-fecha_nacimiento"></span>
            </div>

            <div class="input-group">
                <h1>Fotografía</h1>
                <input type="file" id="foto" name="foto">
                <span class="error-message" id="error-foto"></span>
            </div>

            <div class="input-group">
                <h1>Contraseña</h1>
                <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña">
                <span class="error-message" id="error-password"></span>
            </div>

        
            <button type="submit" class="btn-login">Registrar</button>

            <p class= "register-link">
¿Ya tienes cuenta? <a href="Login.php">Inicia Sesión</a>

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

            const usuario = document.getElementById('usuario').value.trim();
            if (usuario === '') {
                mostrarError('usuario', 'El nombre completo es obligatorio.');
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
            } else if (!/[A-Z]/.test(password)) {
                mostrarError('password', 'Debe incluir al menos una letra mayúscula.');
                esValido = false;
            } else if (!/\d/.test(password)) {
                mostrarError('password', 'Debe incluir al menos un número.');
                esValido = false;
            } else if (!/[^A-Za-z0-9]/.test(password)) {
                mostrarError('password', 'Debe incluir al menos un carácter especial (# $ % & * ! ? etc).');
                esValido = false;
            }

            return esValido;
        }

        form.addEventListener('submit', function (evento) {
            evento.preventDefault();

            if (validarFormulario()) {
                alert('Se registró el usuario correctamente.');
            }
        });
    </script>
</body>
</html>
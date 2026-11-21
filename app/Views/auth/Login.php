<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css">
</head>
<body>
    <div class="login-container">
        <form class="login-box" action="<?= $base ?>/login" method="POST" id="login-form" novalidate>
            <h1>Iniciar Sesión</h1>

            <?php if (!empty($error)): ?>
                <div class="alerta alerta-error" role="alert"><?= e($error) ?></div>
            <?php endif; ?>
            <?php if (!empty($exito)): ?>
                <div class="alerta alerta-exito" role="status"><?= e($exito) ?></div>
            <?php endif; ?>

            <input type="hidden" name="csrf" value="<?= e($csrf) ?>">
            <div class="alerta alerta-error" id="login-api-error" role="alert" hidden></div>

            <div class="input-group">
                <h1>Email</h1>
                <input type="email" id="email" name="email" placeholder="Ingresa tu email"
                       value="<?= e($anterior['email'] ?? '') ?>" required>
            </div>

            <div class="input-group">
                <h1>Contraseña</h1>
                <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
            </div>

            <button type="submit" class="btn-login">Entrar</button>

            <p class="register-link">
                ¿No tienes cuenta? <a href="<?= $base ?>/registro">Regístrate</a>
            </p>
        </form>
    </div>
    <script>
    (() => {
        const form = document.getElementById('login-form');
        const errorBox = document.getElementById('login-api-error');
        const base = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        form.addEventListener('submit', async (event) => {
            event.preventDefault();
            errorBox.hidden = true;
            errorBox.textContent = '';
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            if (!email || !password) {
                errorBox.textContent = 'Escribe tu correo y contraseña.';
                errorBox.hidden = false;
                return;
            }
            const button = form.querySelector('button[type="submit"]');
            button.disabled = true;
            button.textContent = 'Validando...';
            try {
                const response = await fetch(`${base}/api/auth/login`, {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
                    body: JSON.stringify({ email, password })
                });
                const json = await response.json();
                if (!response.ok || json.ok !== true) {
                    throw new Error(json.error?.mensaje || `Error HTTP ${response.status}`);
                }
                window.location.assign(`${base}/`);
            } catch (error) {
                errorBox.textContent = error.message || 'No se pudo conectar con el servidor.';
                errorBox.hidden = false;
                button.disabled = false;
                button.textContent = 'Entrar';
            }
        });
    })();
    </script>
</body>
</html>

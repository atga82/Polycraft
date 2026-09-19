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
        <form class="login-box" action="Menu.php" method="GET">
            <h1>Iniciar Sesión</h1>
 
            <div class="input-group">
                <h1>Usuario</h1>
                <input type="text" id="usuario" name="usuario" placeholder="Ingresa tu usuario" required>
            </div>
 
            <div class="input-group">
                <h1>Contraseña</h1>
                <input type="password" id="password" name="password" placeholder="Ingresa tu contraseña" required>
            </div>
 
            <button type="submit" class="btn-login">Entrar</button>
            

            <p class= "register-link">
¿No tienes cuenta? <a href="Register.php">Regístrate</a>

            </p>
        </form>
    </div>
</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte de Usuarios</title>
    <link rel="stylesheet" href="stylemenu.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="Menu.php" class="logo">Cursos</a>
            <a href="RegisterCourse.php" class="nav-link">Crear Curso</a>
            <a href="Kardex.php" class="nav-link">Kardex</a>
            <a href="Ventas.php" class="nav-link">Ventas</a>
            <a href="Chat.php" class="nav-link">Chat</a>     
            <a href="Reports.php" class="nav-link">Reporte</a>
        </div>

        <form class="navbar-search" action="#" method="GET">
            <input type="text" name="buscar" placeholder="Buscar Cursos" class="search-input">
        </form>

        <div class="navbar-right">
            <a href="Login.php" class="btn btn-outline">Iniciar sesión</a>
            <a href="Register.php" class="btn btn-filled">Regístrate</a>
        </div>
    </nav>

    <div class="page-content wide">

        <h1 class="page-title">Reporte de Usuarios</h1>

        <form class="filter-bar" action="#" method="GET">
            <div class="filter-field">
                <label for="tipo_usuario">Tipo de usuario</label>
                <select id="tipo_usuario" name="tipo_usuario">
                    <option value="instructor">Instructor</option>
                    <option value="estudiante">Estudiante</option>
                </select>
            </div>

            <button type="submit" class="btn btn-filled filter-btn">Generar reporte</button>
        </form>

       

    </div>

</body>
</html>
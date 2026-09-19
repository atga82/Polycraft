<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mensajes</title>
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

    <div class="messages-layout">

        <aside class="conversation-list">
            <h2 class="sidebar-title">Conversaciones</h2>

           
        </aside>

        

    </div>

</body>
</html>
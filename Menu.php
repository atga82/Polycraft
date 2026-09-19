<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link rel="stylesheet" href="stylemenu.css">

</head>
<body>

        <!-- NAVBAR -->

 
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

            <!-- NAVBAR FIN-->


    <!-- Para las categorías -->
    <div class="page-layout">
 
        <aside class="sidebar">
            <h2 class="sidebar-title">Categorías</h2>
            <ul class="category-list">
                <li><a href="#">Todas</a></li>
                <li><a href="#">Marketing</a></li>
                <li><a href="#">Diseño</a></li>
                <li><a href="#">Negocios</a></li>
                <li><a href="#">Fotografía</a></li>
            </ul>
        </aside>


    </div>
 
</body>
</html>
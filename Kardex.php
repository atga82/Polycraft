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


 





<div class="page-content">
 
        <h1 class="page-title">Mi Kardex</h1>
 
        <form class="filter-bar" action="#" method="GET">
            <div class="filter-field">
                <label for="fecha_desde">Inscrito desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde">
            </div>
 
            <div class="filter-field">
                <label for="fecha_hasta">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta">
            </div>
 
            <div class="filter-field">
                <label for="categoria">Categoría</label>
                <select id="categoria" name="categoria">
                    <option value="todas">Todas</option>
                    <option value="marketing">Marketing</option>
                    <option value="diseno">Diseño</option>
                </select>
            </div>
 
            <div class="filter-field">
                <label for="estado">Estado del curso</label>
                <select id="estado" name="estado">
                    <option value="todos">Todos</option>
                    <option value="terminados">Solo terminados</option>
                </select>
            </div>
 
            <div class="filter-field">
                <label for="activos">Cursos</label>
                <select id="activos" name="activos">
                    <option value="todos">Todos</option>
                    <option value="activos">Solo activos</option>
                </select>
            </div>
 
            <button type="submit" class="btn btn-filled filter-btn">Filtrar</button>
        </form>
 
       
 
    </div>


</body>
</html>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Curso</title>
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
 
    <div class="page-content">
 
        <h1 class="page-title">Crear Curso</h1>
 
        <form class="form-card" action="#" method="POST" enctype="multipart/form-data">
 
            <div class="filter-field">
                <label for="titulo">Título del curso</label>
                <input type="text" id="titulo" name="titulo" placeholder="Ej. Introducción a JavaScript" required>
            </div>
 
            <div class="filter-field">
                <label for="descripcion">Descripción general</label>
                <textarea id="descripcion" name="descripcion" rows="4" placeholder="¿De qué trata el curso?" required></textarea>
            </div>
 
            <div class="filter-field">
                <label for="imagen_curso">Imagen del curso</label>
                <input type="file" id="imagen_curso" name="imagen_curso" accept="image/*" required>
            </div>
 
            <div class="filter-field">
                <label>Categorías</label>
                <div class="checkbox-list">
                    <label><input type="checkbox" name="categorias[]" value="marketing"> Marketing</label>
                    <label><input type="checkbox" name="categorias[]" value="diseno"> Diseño</label>
                    <label><input type="checkbox" name="categorias[]" value="negocios"> Negocios</label>
                    <label><input type="checkbox" name="categorias[]" value="fotografia"> Fotografía</label>
                </div>
            </div>
 
            <div class="filter-field">
                <label for="costo_completo">Costo del curso completo</label>
                <input type="number" id="costo_completo" name="costo_completo" step="0.01" min="0" placeholder="0.00 = gratis">
            </div>
 



            <hr class="form-divider">
 
 
            
 
 
            <button type="submit" class="btn btn-filled save-course-btn">Publicar curso</button>
 
        </form>
 
    </div>
 
</body>
</html>
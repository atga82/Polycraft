<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Polycraft | Catálogo de cursos</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/stylemenu.css">
</head>
<body>
    <nav class="navbar">
        <div class="navbar-left">
            <a href="<?= $base ?>/" class="logo">Polycraft</a>
            <?php if (($usuario['rol'] ?? '') === 'instructor' || ($usuario['rol'] ?? '') === 'administrador'): ?>
                <a href="<?= $base ?>/crear-curso" class="nav-link">Crear curso</a>
                <a href="<?= $base ?>/ventas" class="nav-link">Ventas</a>
            <?php endif; ?>
            <?php if (($usuario['rol'] ?? '') === 'estudiante'): ?>
                <a href="<?= $base ?>/kardex" class="nav-link">Kardex</a>
            <?php endif; ?>
            <a href="<?= $base ?>/chat" class="nav-link">Chat</a>
            <?php if (($usuario['rol'] ?? '') === 'administrador'): ?>
                <a href="<?= $base ?>/reportes" class="nav-link">Reportes</a>
            <?php endif; ?>
        </div>
        <form class="navbar-search" action="<?= $base ?>/" method="GET" id="catalog-search">
            <input type="search" name="buscar" id="search-input" placeholder="Buscar cursos o instructores" class="search-input" value="<?= e($_GET['buscar'] ?? '') ?>">
            <button type="submit" class="btn btn-outline">Buscar</button>
        </form>
        <div class="navbar-right"><?php include BASE_PATH . '/app/Views/partials/sesion.php'; ?></div>
    </nav>

    <div class="page-layout">
        <aside class="sidebar">
            <h2 class="sidebar-title">Categorías</h2>
            <ul class="category-list" id="category-list"><li><span>Cargando...</span></li></ul>
        </aside>
        <main class="content">
            <section class="course-section">
                <h1 class="section-title" id="catalog-title">Cursos recientes</h1>
                <p id="catalog-status" role="status" aria-live="polite">Consultando la API de Polycraft...</p>
                <div class="course-grid" id="course-grid"></div>
            </section>
        </main>
    </div>

    <script>
    (() => {
        const apiCursos = <?= json_encode($base . '/api/cursos', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const apiCategorias = <?= json_encode($base . '/api/categorias', JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const base = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const grid = document.getElementById('course-grid');
        const status = document.getElementById('catalog-status');
        const form = document.getElementById('catalog-search');
        const search = document.getElementById('search-input');
        let categoriaActual = '';

        async function leerJSON(url) {
            const response = await fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' });
            const body = await response.json();
            if (!response.ok || body.ok !== true) {
                throw new Error(body.error?.mensaje || `Error HTTP ${response.status}`);
            }
            return body.data;
        }
        function nodo(tag, clase, texto) {
            const element = document.createElement(tag);
            if (clase) element.className = clase;
            if (texto !== undefined) element.textContent = texto;
            return element;
        }
        function precio(value) {
            return Number(value) === 0 ? 'Gratis' : new Intl.NumberFormat('es-MX', { style: 'currency', currency: 'MXN' }).format(Number(value));
        }
        function renderCursos(data) {
            grid.replaceChildren();
            const cursos = data.cursos || [];
            document.getElementById('catalog-title').textContent = categoriaActual ? `Cursos: ${categoriaActual}` : (search.value.trim() ? 'Resultados de búsqueda' : 'Cursos recientes');
            if (!cursos.length) {
                status.textContent = 'No hay cursos activos que coincidan con la consulta.';
                return;
            }
            status.textContent = `${cursos.length} curso(s) encontrado(s).`;
            cursos.forEach(curso => {
                const link = nodo('a', 'course-card');
                link.href = `${base}/curso?id=${encodeURIComponent(curso.id)}`;
                const thumb = nodo('div', 'course-thumb');
                const src = String(curso.imagen || '');
                if (/^(https?:\/\/|\/)/i.test(src) && !/^\/\//.test(src)) {
                    const img = document.createElement('img');
                    img.src = src.startsWith('/') ? `${base}${src}` : src;
                    img.alt = `Imagen de ${curso.titulo}`;
                    img.loading = 'lazy';
                    img.onerror = () => { thumb.replaceChildren(); thumb.textContent = 'Imagen no disponible'; };
                    thumb.replaceChildren(img);
                } else {
                    thumb.textContent = 'Curso Polycraft';
                }
                const info = nodo('div', 'course-info');
                info.append(nodo('h3', 'course-title', curso.titulo));
                info.append(nodo('p', 'course-instructor', `Instructor: ${curso.instructor || 'No disponible'}`));
                info.append(nodo('p', 'course-rating', `Calificación: ${Number(curso.promedio_calificacion || 0).toFixed(1)} / 5`));
                if (curso.categorias) info.append(nodo('p', 'course-instructor', curso.categorias));
                info.append(nodo('p', 'course-price', precio(curso.precio)));
                link.append(thumb, info);
                grid.append(link);
            });
        }
        async function cargarCursos() {
            status.textContent = 'Consultando la API de Polycraft...';
            const params = new URLSearchParams();
            params.set('activo', 'true');
            if (search.value.trim()) params.set('buscar', search.value.trim());
            if (categoriaActual) params.set('categoria', categoriaActual);
            try {
                const data = await leerJSON(`${apiCursos}?${params.toString()}`);
                renderCursos(data);
            } catch (error) {
                grid.replaceChildren();
                status.textContent = `No se pudo cargar el catálogo: ${error.message}. Revisa que PHP y MySQL estén activos.`;
            }
        }
        async function cargarCategorias() {
            const list = document.getElementById('category-list');
            try {
                const categorias = await leerJSON(apiCategorias);
                list.replaceChildren();
                const todas = nodo('a', '', 'Todas');
                todas.href = '#';
                todas.addEventListener('click', e => { e.preventDefault(); categoriaActual = ''; cargarCursos(); });
                const primero = nodo('li', '', ''); primero.append(todas); list.append(primero);
                categorias.forEach(cat => {
                    const a = nodo('a', '', cat.nombre);
                    a.href = '#';
                    a.addEventListener('click', e => { e.preventDefault(); categoriaActual = cat.nombre; cargarCursos(); });
                    const li = nodo('li', '', ''); li.append(a); list.append(li);
                });
            } catch (error) {
                list.replaceChildren(nodo('li', '', 'No se pudieron cargar las categorías.'));
            }
        }
        form.addEventListener('submit', event => { event.preventDefault(); categoriaActual = ''; cargarCursos(); });
        cargarCategorias();
        cargarCursos();
    })();
    </script>
</body>
</html>

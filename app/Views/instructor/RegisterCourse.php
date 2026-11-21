<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Curso</title>
    <link rel="stylesheet" href="<?= $base ?>/assets/css/stylemenu.css">
</head>
<body>

    <nav class="navbar">
        <div class="navbar-left">
            <a href="<?= $base ?>/" class="logo">Cursos</a>
            <a href="<?= $base ?>/crear-curso" class="nav-link">Crear Curso</a>
            <a href="<?= $base ?>/kardex" class="nav-link">Kardex</a>
            <a href="<?= $base ?>/ventas" class="nav-link">Ventas</a>
            <a href="<?= $base ?>/chat" class="nav-link">Chat</a>
            <a href="<?= $base ?>/reportes" class="nav-link">Reporte</a>
        </div>

        <form class="navbar-search" action="#" method="GET">
            <input type="text" name="buscar" placeholder="Buscar Cursos" class="search-input">
        </form>

        <div class="navbar-right">
            <?php include BASE_PATH . '/app/Views/partials/sesion.php'; ?>
        </div>
    </nav>

    <div class="page-content narrow">

        <h1 class="page-title">Crear Curso</h1>
        <p class="page-subtitle">Publica tu curso con niveles, videos y materiales mediante la API propia.</p>
        <p id="course-alert" role="status" aria-live="polite"></p>

        <form action="#" method="POST" enctype="multipart/form-data" id="course-form">

            <!-- 1. Información general -->
            <div class="form-card">
                <h2 class="form-subtitle">1. Información general</h2>

                <div class="filter-field">
                    <label for="titulo">Título del curso</label>
                    <input type="text" id="titulo" name="titulo" placeholder="Ej. Introducción a JavaScript" required>
                </div>

                <div class="filter-field">
                    <label for="descripcion">Descripción general</label>
                    <textarea id="descripcion" name="descripcion" rows="4" placeholder="¿De qué trata el curso? ¿Qué aprenderá el estudiante?" required></textarea>
                </div>

                <div class="form-grid">
                    <div class="filter-field">
                        <label for="imagen_curso">URL de la imagen del curso</label>
                        <input type="url" id="imagen_curso" name="imagen_curso" placeholder="https://ejemplo.com/portada.jpg">
                        <span class="field-hint">O sube una imagen desde tu equipo:</span>
                        <input type="file" id="imagen_archivo" accept="image/jpeg,image/png,image/webp">
                    </div>

                    <div class="filter-field">
                        <label for="costo_completo">Costo del curso completo</label>
                        <div class="input-prefix">
                            <span>$</span>
                            <input type="number" id="costo_completo" name="costo_completo" step="0.01" min="0" placeholder="0.00">
                        </div>
                        <span class="field-hint">Deja 0.00 si el curso completo es gratis.</span>
                    </div>
                </div>
            </div>

            <!-- 2. Categorías -->
            <div class="form-card">
                <h2 class="form-subtitle">2. Categorías</h2>
                <p class="field-hint spaced">Elige al menos una.</p>

                <div class="chip-list" id="categorias-chips"><p>Cargando categorías desde la API...</p></div>
            </div>

            <!-- 3. Niveles -->
            <div class="form-card">
                <h2 class="form-subtitle">3. Niveles del curso</h2>
                <p class="field-hint spaced">Cada nivel necesita un video. También puedes agregar un PDF o un enlace externo.</p>

                <div id="levels"></div>

                <button type="button" class="btn btn-outline add-level-btn" id="add-level">+ Agregar nivel</button>
            </div>

            <div class="form-actions">
                <a href="<?= $base ?>/" class="btn btn-outline">Cancelar</a>
                <button type="submit" class="btn btn-filled save-course-btn">Publicar curso</button>
            </div>

        </form>
    </div>

    <!-- Plantilla de un nivel -->
    <template id="level-template">
        <div class="level-editor">
            <div class="level-editor-header">
                <span class="level-number">Nivel</span>
                <div class="level-header-right">
                    <label class="level-free-check">
                        <input type="checkbox" name="niveles[__i__][gratis]" class="free-toggle"> Nivel gratis
                    </label>
                    <button type="button" class="btn btn-danger small-btn remove-level">Quitar</button>
                </div>
            </div>

            <div class="filter-field">
                <label>Título del nivel</label>
                <input type="text" name="niveles[__i__][titulo]" placeholder="Ej. Variables y tipos de datos" required>
            </div>

            <div class="filter-field">
                <label>Descripción del nivel</label>
                <textarea name="niveles[__i__][descripcion]" rows="2" placeholder="Breve resumen del contenido"></textarea>
            </div>

            <div class="form-grid">
                <div class="filter-field">
                    <label>URL del video o selecciona un archivo (obligatorio uno de los dos)</label>
                    <input type="url" name="niveles[__i__][video_url]" placeholder="https://ejemplo.com/clase.mp4">
                    <input type="file" name="niveles[__i__][video_archivo]" accept="video/mp4,video/webm">
                </div>
                <div class="filter-field">
                    <label>Costo del nivel</label>
                    <div class="input-prefix">
                        <span>$</span>
                        <input type="number" class="level-cost" name="niveles[__i__][costo]" step="0.01" min="0" placeholder="0.00">
                    </div>
                </div>
            </div>

            <div class="form-grid">
                <div class="filter-field">
                    <label>URL del PDF o archivo PDF de apoyo (opcional)</label>
                    <input type="url" name="niveles[__i__][pdf_url]" placeholder="https://ejemplo.com/material.pdf">
                    <input type="file" name="niveles[__i__][pdf_archivo]" accept="application/pdf">
                </div>
                <div class="filter-field">
                    <label>Enlace externo (opcional)</label>
                    <input type="url" name="niveles[__i__][link]" placeholder="https://...">
                </div>
            </div>
        </div>
    </template>

    <script>
        const levels = document.getElementById('levels');
        const tpl = document.getElementById('level-template');
        let counter = 0;

        function renumber() {
            levels.querySelectorAll('.level-editor').forEach((el, idx) => {
                el.querySelector('.level-number').textContent = 'Nivel ' + (idx + 1);
                // El primer nivel no se puede quitar: un curso necesita al menos uno
                el.querySelector('.remove-level').style.display = levels.children.length === 1 ? 'none' : '';
            });
        }

        function addLevel() {
            const html = tpl.innerHTML.replaceAll('__i__', counter++);
            const wrapper = document.createElement('div');
            wrapper.innerHTML = html.trim();
            const level = wrapper.firstElementChild;

            level.querySelector('.remove-level').addEventListener('click', () => {
                level.remove();
                renumber();
            });

            level.querySelector('.free-toggle').addEventListener('change', (e) => {
                const cost = level.querySelector('.level-cost');
                cost.disabled = e.target.checked;
                if (e.target.checked) cost.value = '';
            });

            levels.appendChild(level);
            renumber();
        }

        document.getElementById('add-level').addEventListener('click', addLevel);
        addLevel(); // empieza con un nivel
    </script>

    <script>
    (() => {
        const base = <?= json_encode($base, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
        const form = document.getElementById('course-form');
        const alert = document.getElementById('course-alert');
        const save = form.querySelector('.save-course-btn');
        const categorias = document.getElementById('categorias-chips');

        async function api(path, options = {}) {
            const res = await fetch(base + path, {
                credentials: 'same-origin',
                headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
                ...options
            });
            const j = res.status === 204 ? {ok: true, data: null} : await res.json();
            if (!res.ok || !j.ok) throw new Error(j.error?.mensaje || 'Error HTTP ' + res.status);
            return j.data;
        }

        async function subirArchivo(archivo, tipo) {
            const datos = new FormData();
            datos.append('tipo', tipo);
            datos.append('archivo', archivo);
            const res = await fetch(base + '/api/archivos', {
                method: 'POST', credentials: 'same-origin',
                headers: {'Accept':'application/json'}, body: datos
            });
            const j = await res.json();
            if (!res.ok || !j.ok) throw new Error(j.error?.mensaje || 'No se pudo subir el archivo');
            return j.data.url;
        }

        async function cargarCategorias() {
            try {
                const lista = await api('/api/categorias');
                categorias.replaceChildren();
                if (!lista.length) {
                    alert.textContent = 'Un administrador debe crear una categoría antes de publicar cursos.';
                    return;
                }
                lista.forEach(c => {
                    const chip = document.createElement('label');
                    chip.className = 'category-chip';
                    const input = document.createElement('input');
                    input.type = 'checkbox'; input.name = 'categorias[]'; input.value = c.id;
                    const texto = document.createElement('span'); texto.textContent = c.nombre;
                    chip.append(input, texto); categorias.append(chip);
                });
            } catch (error) { alert.textContent = 'No se pudieron consultar categorías: ' + error.message; }
        }

        form.addEventListener('submit', async event => {
            event.preventDefault();
            alert.textContent = '';
            const seleccion = [...form.querySelectorAll('input[name="categorias[]"]:checked')].map(n => Number(n.value));
            if (!seleccion.length) { alert.textContent = 'Selecciona al menos una categoría.'; return; }
            const niveles = [...document.querySelectorAll('#levels .level-editor')].map(n => ({
                titulo: n.querySelector('input[name$="[titulo]"]').value.trim(),
                descripcion: n.querySelector('textarea[name$="[descripcion]"]').value.trim(),
                video_url: n.querySelector('input[name$="[video_url]"]').value.trim(),
                video_archivo: n.querySelector('input[name$="[video_archivo]"]').files[0] || null,
                precio: n.querySelector('.free-toggle').checked ? 0 : Number(n.querySelector('.level-cost').value || 0),
                pdf_url: n.querySelector('input[name$="[pdf_url]"]').value.trim(),
                pdf_archivo: n.querySelector('input[name$="[pdf_archivo]"]').files[0] || null,
                link_url: n.querySelector('input[name$="[link]"]').value.trim(),
            }));
            if (!niveles.length || niveles.some(n => !n.titulo || (!n.video_url && !n.video_archivo) || n.precio < 0)) {
                alert.textContent = 'Cada nivel necesita título, video (URL o archivo) y precio válido.';
                return;
            }
            save.disabled = true;
            let creado = 0;
            try {
                const imagenArchivo = document.getElementById('imagen_archivo').files[0];
                const imagen = imagenArchivo
                    ? await subirArchivo(imagenArchivo, 'imagen')
                    : (document.getElementById('imagen_curso').value.trim() || 'default-course.png');
                const curso = await api('/api/cursos', {
                    method: 'POST',
                    body: JSON.stringify({
                        titulo: document.getElementById('titulo').value.trim(),
                        descripcion: document.getElementById('descripcion').value.trim(),
                        imagen,
                        precio: Number(document.getElementById('costo_completo').value || 0),
                        categorias: seleccion
                    })
                });
                creado = curso.id;
                for (const nivel of niveles) {
                    if (nivel.video_archivo) nivel.video_url = await subirArchivo(nivel.video_archivo, 'video');
                    if (nivel.pdf_archivo) nivel.pdf_url = await subirArchivo(nivel.pdf_archivo, 'pdf');
                    const n = await api('/api/cursos/' + creado + '/niveles', {
                        method: 'POST',
                        body: JSON.stringify({
                            titulo: nivel.titulo, descripcion: nivel.descripcion,
                            video_url: nivel.video_url, precio: nivel.precio
                        })
                    });
                    for (const recurso of [
                        nivel.pdf_url ? {tipo: 'pdf', titulo: 'Material PDF', url: nivel.pdf_url} : null,
                        nivel.link_url ? {tipo: 'enlace', titulo: 'Material externo', url: nivel.link_url} : null
                    ].filter(Boolean)) {
                        await api('/api/niveles/' + n.id + '/recursos', {
                            method: 'POST', body: JSON.stringify(recurso)
                        });
                    }
                }
                alert.textContent = 'Curso y ' + niveles.length + ' niveles publicados correctamente.';
                form.reset();
                setTimeout(() => { window.location.assign(base + '/curso?id=' + creado); }, 1200);
            } catch (error) {
                alert.textContent = creado
                    ? 'Curso #' + creado + ' guardado parcialmente: ' + error.message + '. Completa los niveles antes de ofrecerlo.'
                    : 'No se pudo publicar el curso: ' + error.message;
            } finally { save.disabled = false; }
        });
        cargarCategorias();
    })();
    </script>

</body>
</html>
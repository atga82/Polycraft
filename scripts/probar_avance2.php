<?php
declare(strict_types=1);

/**
 * Prueba de integración local de la API propia, sin efectuar pagos.
 * Uso: C:\xampp\php\php.exe scripts\probar_avance2.php
 * Genera 2 cuentas QA y 1 curso QA para preservar datos existentes.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }

$base = 'http://localhost/Polycraft-main';
$cookieAnon = tempnam(sys_get_temp_dir(), 'pc_anon_');
$cookieInstructor = tempnam(sys_get_temp_dir(), 'pc_inst_');
$cookieEstudiante = tempnam(sys_get_temp_dir(), 'pc_est_');
$pasaron = 0;
$fallaron = 0;
$fallaDetalles = [];

function peticion(string $metodo, string $ruta, ?array $json = null, ?string $cookie = null, array $headers = []): array
{
    global $base;
    $ch = curl_init($base . $ruta);
    $cabeceras = array_merge(['Accept: application/json'], $headers);
    $opciones = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $metodo,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_HTTPHEADER => $cabeceras,
    ];
    if ($cookie !== null) {
        $opciones[CURLOPT_COOKIEFILE] = $cookie;
        $opciones[CURLOPT_COOKIEJAR] = $cookie;
    }
    if ($json !== null) {
        $cabeceras[] = 'Content-Type: application/json';
        $opciones[CURLOPT_HTTPHEADER] = $cabeceras;
        $opciones[CURLOPT_POSTFIELDS] = json_encode($json, JSON_UNESCAPED_UNICODE);
    }
    curl_setopt_array($ch, $opciones);
    $body = curl_exec($ch);
    $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    return ['status' => $status, 'data' => json_decode((string)$body, true), 'error' => $error];
}

function probar(string $nombre, int $esperado, array $r, ?callable $verificar = null): void
{
    global $pasaron, $fallaron, $fallaDetalles;
    $bien = $r['status'] === $esperado && ($verificar === null || $verificar($r['data']));
    if ($bien) $pasaron++;
    else {
        $fallaron++;
        $fallaDetalles[] = ['prueba' => $nombre, 'esperado' => $esperado, 'obtenido' => $r['status'],
            'error' => $r['error'], 'mensaje' => $r['data']['error']['mensaje'] ?? null];
    }
    echo ($bien ? '[OK] ' : '[FALLO] ') . $nombre . ' - HTTP ' . $r['status'] . PHP_EOL;
}

try {
    $r = peticion('GET', '/api/health', null, $cookieAnon);
    probar('Health', 200, $r, fn($d) => ($d['ok'] ?? false) === true);
    $r = peticion('GET', '/api/categorias', null, $cookieAnon);
    probar('Categorías públicas', 200, $r, fn($d) => is_array($d['data'] ?? null));
    $categoria = (int)($r['data']['data'][0]['id'] ?? 0);
    $r = peticion('GET', '/api/cursos', null, $cookieAnon);
    probar('Catálogo público', 200, $r, fn($d) => is_array($d['data']['cursos'] ?? null));
    probar('Filtro activo inválido', 400, peticion('GET', '/api/cursos?activo=false', null, $cookieAnon));
    probar('Detalle exige sesión', 401, peticion('GET', '/api/cursos/1', null, $cookieAnon));
    probar('Crear curso sin sesión', 401, peticion('POST', '/api/cursos', ['titulo' => 'Sin permiso'], $cookieAnon));
    probar('Compra sin sesión', 401, peticion('POST', '/api/compras', ['curso_id' => 1], $cookieAnon));
    probar('Usuarios sin sesión', 401, peticion('GET', '/api/usuarios', null, $cookieAnon));
    probar('Registro de administrador prohibido', 422, peticion('POST', '/api/auth/registro', [
        'rol' => 'administrador', 'nombre_completo' => 'QA Admin', 'email' => 'qa@example.local',
        'genero' => 'otro', 'fecha_nacimiento' => '2000-01-01', 'password' => 'QaPrueba1!'
    ], $cookieAnon));

    $unico = date('YmdHis') . bin2hex(random_bytes(3));
    $password = 'QaPrueba1!';
    $instructor = "qa.instructor.$unico@polycraft.local";
    $estudiante = "qa.estudiante.$unico@polycraft.local";
    $registro = fn($rol, $email) => ['rol' => $rol, 'nombre_completo' => 'Usuario QA ' . $rol,
        'email' => $email, 'genero' => 'otro', 'fecha_nacimiento' => '2000-01-01', 'password' => $password];
    $registroInst=peticion('POST', '/api/auth/registro', $registro('instructor', $instructor), $cookieAnon);
    probar('Registrar instructor QA', 201, $registroInst);
    $instructorId=(int)($registroInst['data']['data']['id']??0);
    probar('Login instructor QA', 200, peticion('POST', '/api/auth/login',
        ['email' => $instructor, 'password' => $password], $cookieInstructor));
    probar('Perfil instructor', 200, peticion('GET', '/api/perfil', null, $cookieInstructor));
    // Prueba de subida multipart con un PNG real de 1 x 1, sin tocar archivos del usuario.
    $pngTemporal=tempnam(sys_get_temp_dir(),'pc_png_');
    file_put_contents($pngTemporal,base64_decode(
        'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVQIHWP4z8DwHwAFgAI/ScL+nAAAAABJRU5ErkJggg=='));
    $ch=curl_init($base.'/api/archivos');
    curl_setopt_array($ch,[
        CURLOPT_RETURNTRANSFER=>true,CURLOPT_POST=>true,
        CURLOPT_COOKIEFILE=>$cookieInstructor,CURLOPT_COOKIEJAR=>$cookieInstructor,
        CURLOPT_HTTPHEADER=>['Accept: application/json'],
        CURLOPT_POSTFIELDS=>['tipo'=>'imagen','archivo'=>new CURLFile($pngTemporal,'image/png','qa.png')]
    ]);
    $uploadBody=curl_exec($ch);
    $uploadStatus=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);
    curl_close($ch);
    if(is_file($pngTemporal))unlink($pngTemporal);
    probar('Subir archivo PNG multipart',201,
        ['status'=>$uploadStatus,'data'=>json_decode((string)$uploadBody,true),'error'=>'']);
    probar('Rechaza ID de curso inválido', 400, peticion('GET', '/api/cursos/abc', null, $cookieInstructor));
    probar('Rechaza curso con precio negativo', 422, peticion('POST', '/api/cursos',
        ['titulo' => 'Curso QA', 'descripcion' => 'Prueba de precio inválido', 'precio' => -1,
         'categorias' => [$categoria]], $cookieInstructor));
    $cursoId = 0;
    if ($categoria > 0) {
        $curso = peticion('POST', '/api/cursos', [
            'titulo' => 'QA Polycraft ' . $unico,
            'descripcion' => 'Curso de prueba automatizada; no representa una venta.',
            'imagen' => 'https://example.com/qa-course.jpg', 'precio' => 250,
            'categorias' => [$categoria]], $cookieInstructor);
        probar('Crear curso como instructor', 201, $curso, fn($d) => isset($d['data']['id']));
        $cursoId = (int)($curso['data']['data']['id'] ?? 0);
    } else {
        echo '[OMITIDO] Crear curso: no existen categorías; crea una con el administrador.' . PHP_EOL;
    }
    if ($cursoId > 0) {
        probar('Detalle del curso', 200, peticion('GET', '/api/cursos/' . $cursoId, null, $cookieInstructor));
        probar('Actualizar curso del propietario', 200, peticion('PATCH', '/api/cursos/' . $cursoId,
            ['descripcion' => 'Descripción verificada con PATCH de API propia.'], $cookieInstructor));
        probar('Filtrar por categoría', 200, peticion('GET', '/api/cursos?categoria=' . $categoria, null, $cookieAnon));
    }
    $nivelId=0;
    $cursoGratis=0;
    if ($cursoId > 0) {
        $nivel=peticion('POST','/api/cursos/'.$cursoId.'/niveles',[
            'titulo'=>'Nivel 1 de QA', 'descripcion'=>'Contenido inicial',
            'contenido'=>'Curso con texto, video y recursos de ejemplo.',
            'video_url'=>'https://example.com/leccion-qa.mp4', 'precio'=>0
        ],$cookieInstructor);
        probar('Crear nivel con video',201,$nivel);
        $nivelId=(int)($nivel['data']['data']['id']??0);
        if ($nivelId>0) {
            probar('Agregar recurso externo',201,peticion('POST','/api/niveles/'.$nivelId.'/recursos',
                ['tipo'=>'enlace','titulo'=>'Referencia QA','url'=>'https://example.com/curso'], $cookieInstructor));
            probar('Listar niveles al propietario',200,peticion('GET','/api/cursos/'.$cursoId.'/niveles',null,$cookieInstructor));
        }
        probar('Ventas resumen del instructor',200,peticion('GET','/api/ventas',null,$cookieInstructor));
        probar('Ventas detalle del curso',200,peticion('GET','/api/ventas/'.$cursoId,null,$cookieInstructor));
    }
    if ($categoria>0) {
        $g=peticion('POST','/api/cursos',[
            'titulo'=>'QA Gratuito '.$unico, 'descripcion'=>'Curso gratuito de prueba.',
            'imagen'=>'https://example.com/gratis.png', 'precio'=>0, 'categorias'=>[$categoria]
        ],$cookieInstructor);
        probar('Crear curso gratuito',201,$g);
        $cursoGratis=(int)($g['data']['data']['id']??0);
        if ($cursoGratis>0) {
            probar('Agregar nivel al curso gratuito',201,peticion('POST','/api/cursos/'.$cursoGratis.'/niveles',
                ['titulo'=>'Gratis 1','video_url'=>'https://example.com/gratis.mp4','precio'=>0],$cookieInstructor));
        }
    }
    probar('Instructor no puede crear categoría', 403, peticion('POST', '/api/categorias',
        ['nombre' => 'QA no permitido'], $cookieInstructor));
    probar('Instructor no puede listar usuarios', 403, peticion('GET', '/api/usuarios', null, $cookieInstructor));

    probar('Registrar estudiante QA', 201, peticion('POST', '/api/auth/registro', $registro('estudiante', $estudiante), $cookieAnon));
    probar('Login estudiante QA', 200, peticion('POST', '/api/auth/login',
        ['email' => $estudiante, 'password' => $password], $cookieEstudiante));
    probar('Perfil estudiante', 200, peticion('GET', '/api/perfil', null, $cookieEstudiante));
    probar('Estudiante no puede crear cursos', 403, peticion('POST', '/api/cursos',
        ['titulo' => 'Sin permiso'], $cookieEstudiante));
    if ($cursoId > 0) {
        probar('Nivel gratuito visible antes de comprar', 200,
            peticion('GET', '/api/cursos/' . $cursoId, null, $cookieEstudiante),
            fn($d) => ($d['data']['acceso_contenido'] ?? true) === false &&
                isset($d['data']['niveles'][0]['video_url']));
        probar('Estudiante no modifica cursos', 403, peticion('PATCH', '/api/cursos/' . $cursoId,
            ['titulo' => 'Sin permiso'], $cookieEstudiante));
    }
    if ($cursoGratis>0) {
        probar('Inscripción gratuita',201,peticion('POST','/api/inscripciones',['curso_id'=>$cursoGratis],$cookieEstudiante));
        probar('Kardex con curso gratuito',200,peticion('GET','/api/inscripciones',null,$cookieEstudiante));
    }
    if ($cursoId>0) {
        $rDemo=peticion('POST','/api/compras',
            ['curso_id'=>$cursoId,'metodo_pago'=>'transferencia_demo'],$cookieEstudiante);
        probar('Compra por transferencia simulada',201,$rDemo,fn($d)=>($d['data']['estado']??'')==='aprobada');
        $compraDemoId=(int)($rDemo['data']['data']['id_compra']??0);
        if ($compraDemoId>0) {
            probar('Consultar compra demo aprobada',200,peticion('GET','/api/compras/'.$compraDemoId,null,$cookieEstudiante));
        }
        probar('Kardex después de compra',200,peticion('GET','/api/inscripciones',null,$cookieEstudiante));
        if ($nivelId>0) {
            $nivelDetalle=peticion('GET','/api/cursos/'.$cursoId.'/niveles',null,$cookieEstudiante);
            probar('Ver materiales al inscribirse',200,$nivelDetalle,fn($d)=>($d['data']['acceso_contenido']??false)===true);
            $progreso=peticion('POST','/api/niveles/'.$nivelId.'/completar',[],$cookieEstudiante);
            probar('Completar nivel y certificado automático',200,$progreso,fn($d)=>($d['data']['terminado']??false)===true);
            $certId=(int)($progreso['data']['data']['certificado']['id']??0);
            if ($certId>0) {
                probar('Consultar certificado propio',200,peticion('GET','/api/certificados/'.$certId,null,$cookieEstudiante));
            }
            probar('Comentar curso terminado',201,peticion('POST','/api/cursos/'.$cursoId.'/comentarios',
                ['calificacion'=>5,'comentario'=>'Contenido útil para el curso QA.'],$cookieEstudiante));
            probar('Listar reseñas',200,peticion('GET','/api/cursos/'.$cursoId.'/comentarios',null,$cookieEstudiante));
        }
    }
    if ($instructorId>0) {
        probar('Contactos chat',200,peticion('GET','/api/contactos',null,$cookieEstudiante));
        probar('Mensaje privado a instructor',201,peticion('POST','/api/mensajes',[
            'destinatario_id'=>$instructorId,'contenido'=>'Hola, probando la mensajería de Polycraft.'
        ],$cookieEstudiante));
        probar('Leer conversación',200,peticion('GET','/api/mensajes?con='.$instructorId,null,$cookieEstudiante));
    }
    probar('Compra de curso inexistente', 404, peticion('POST', '/api/compras',
        ['curso_id' => 99999999], $cookieEstudiante));
    probar('Compra ajena o inexistente', 404, peticion('GET', '/api/compras/99999999', null, $cookieEstudiante));
    probar('CORS origen no autorizado', 403, peticion('GET', '/api/health', null, $cookieAnon,
        ['Origin: http://sitio-no-autorizado.example']));
    probar('Preflight CORS permitido', 204, peticion('OPTIONS', '/api/cursos', null, $cookieAnon,
        ['Origin: http://localhost', 'Access-Control-Request-Method: POST']));
    foreach (['/', '/login', '/registro', '/curso', '/crear-curso', '/kardex', '/ventas', '/chat', '/reportes'] as $pagina) {
        probar('Página ' . $pagina, 200, peticion('GET', $pagina, null, $cookieAnon));
    }

    echo PHP_EOL . 'Resumen: ' . $pasaron . ' correctas, ' . $fallaron . ' fallidas.' . PHP_EOL;
    echo 'No se enviaron órdenes de pago a Mercado Pago.' . PHP_EOL;
    if ($fallaDetalles) echo json_encode($fallaDetalles, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
} finally {
    foreach ([$cookieAnon, $cookieInstructor, $cookieEstudiante] as $temporal) {
        if ($temporal && is_file($temporal)) unlink($temporal);
    }
}
exit($fallaron > 0 ? 1 : 0);

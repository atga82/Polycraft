<?php
/**
 * Configuracion de CORS. Lista EXPLICITA de origenes autorizados
 * (esquema + host + puerto, sin "/" final). Cualquier otro origen recibe 403.
 * Con XAMPP el cliente normalmente vive en http://localhost.
 * Si usas Live Server agrega p. ej. 'http://localhost:5500'.
 */
return [
    'origenes' => [
        'http://localhost',
        'http://127.0.0.1',
    ],
    'metodos'  => 'GET, POST, PUT, PATCH, DELETE, OPTIONS',
    'headers'  => 'Content-Type, Accept',
    'max_age'  => 600,   // segundos que el navegador puede recordar el preflight
];

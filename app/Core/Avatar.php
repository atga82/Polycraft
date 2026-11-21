<?php
declare(strict_types=1);

namespace App\Core;

/** Valida y guarda la imagen de avatar. Lo usan el registro y el perfil. */
final class Avatar
{
    private const MAX_BYTES = 2 * 1024 * 1024;
    private const TIPOS = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    /**
     * Guarda el archivo en public/uploads/avatars y devuelve el nombre generado.
     * @throws HttpException si el archivo no es valido
     */
    public static function guardar(?array $archivo): string
    {
        if ($archivo === null || $archivo['error'] !== UPLOAD_ERR_OK) {
            throw new HttpException(422, 'Datos invalidos', ['avatar' => 'Envia una imagen en el campo "avatar"']);
        }
        if ($archivo['size'] > self::MAX_BYTES) {
            throw new HttpException(413, 'La imagen supera los 2 MB');
        }

        // El tipo se determina por el CONTENIDO real, no por la extension ni el header del cliente.
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($archivo['tmp_name']);
        if (!isset(self::TIPOS[$mime]) || @getimagesize($archivo['tmp_name']) === false) {
            throw new HttpException(415, 'Formato no soportado. Usa JPG, PNG o WEBP');
        }

        $nombre  = bin2hex(random_bytes(16)) . '.' . self::TIPOS[$mime];
        $destino = BASE_PATH . '/public/uploads/avatars/' . $nombre;
        if (!move_uploaded_file($archivo['tmp_name'], $destino)) {
            throw new \RuntimeException('No se pudo guardar el avatar');
        }
        return $nombre;
    }

    public static function borrar(string $nombre): void
    {
        if ($nombre !== 'default.png') {
            @unlink(BASE_PATH . '/public/uploads/avatars/' . basename($nombre));
        }
    }
}

<?php
declare(strict_types=1);
namespace App\Core;

/** Almacenamiento de archivos multimedia con lista blanca y nombres aleatorios. */
final class Archivo
{
    public static function guardar(?array $archivo, string $tipo): string
    {
        $tipos=[
            'imagen'=>['max'=>5*1024*1024,'mimes'=>[
                'image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp']],
            'pdf'=>['max'=>20*1024*1024,'mimes'=>['application/pdf'=>'pdf']],
            'video'=>['max'=>150*1024*1024,'mimes'=>[
                'video/mp4'=>'mp4','video/webm'=>'webm','application/mp4'=>'mp4']],
        ];
        if (!isset($tipos[$tipo])) throw new HttpException(422,'Tipo de archivo no permitido');
        if (!is_array($archivo) || ($archivo['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK) {
            throw new HttpException(422,'No se recibió un archivo válido; revisa el límite de tamaño configurado en PHP');
        }
        if (!is_uploaded_file((string)$archivo['tmp_name'])) {
            throw new HttpException(422,'La carga no provino de una petición válida');
        }
        $max=$tipos[$tipo]['max'];
        if ((int)$archivo['size']<1 || (int)$archivo['size']>$max) {
            throw new HttpException(413,'Archivo demasiado grande para el tipo seleccionado');
        }
        $finfo=new \finfo(FILEINFO_MIME_TYPE);
        $mime=$finfo->file((string)$archivo['tmp_name']);
        $ext=$tipos[$tipo]['mimes'][$mime]??null;
        if (!$ext) throw new HttpException(422,'Tipo MIME no permitido para '.$tipo);
        $carpeta=BASE_PATH.'/public/uploads/courses';
        if (!is_dir($carpeta) && !mkdir($carpeta,0755,true)) {
            throw new \RuntimeException('No fue posible preparar el directorio de archivos');
        }
        $nombre=bin2hex(random_bytes(18)).'.'.$ext;
        if (!move_uploaded_file((string)$archivo['tmp_name'],$carpeta.'/'.$nombre)) {
            throw new \RuntimeException('No fue posible guardar el archivo');
        }
        return '/uploads/courses/'.$nombre;
    }
}

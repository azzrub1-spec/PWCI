<?php

declare(strict_types=1);

namespace App\Services;

final class ImagenPerfil
{
    public function guardar(array &$errors): ?string
    {
        $archivo = $_FILES['imagen_perfil'] ?? null;
        if ($archivo === null) {
            return null;
        }
        if (!is_array($archivo) || !isset($archivo['error'])
            || !is_int($archivo['error'])) {
            $errors[] = 'El archivo recibido no es válido.';
            return null;
        }
        if ($archivo['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if (in_array($archivo['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            $errors[] = 'La imagen supera el tamaño permitido. El máximo es 2 MB.';
            return null;
        }
        if ($archivo['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'No se pudo recibir la imagen. Intenta nuevamente.';
            return null;
        }
        if (!isset($archivo['size'], $archivo['tmp_name'])
            || !is_int($archivo['size']) || !is_string($archivo['tmp_name'])
            || $archivo['size'] <= 0 || $archivo['size'] > 2 * 1024 * 1024) {
            $errors[] = 'Selecciona una imagen de hasta 2 MB.';
            return null;
        }

        $temporal = $archivo['tmp_name'];
        if (!is_uploaded_file($temporal)) {
            $errors[] = 'La carga de la imagen no es válida.';
            return null;
        }
        $informacion = @getimagesize($temporal);
        $tipo = (new \finfo(FILEINFO_MIME_TYPE))->file($temporal);
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png'];
        if ($informacion === false || !isset($tipos[$tipo])
            || $informacion['mime'] !== $tipo) {
            $errors[] = 'Selecciona una imagen JPG o PNG válida.';
            return null;
        }

        $carpeta = dirname(__DIR__, 2) . '/public/uploads/perfiles';
        if (!is_dir($carpeta) && !@mkdir($carpeta, 0755, true)
            && !is_dir($carpeta)) {
            $errors[] = 'No se pudo preparar la carpeta de imágenes.';
            return null;
        }
        $nombre = bin2hex(random_bytes(16)) . '.' . $tipos[$tipo];
        if (!move_uploaded_file($temporal, $carpeta . '/' . $nombre)) {
            $errors[] = 'No se pudo guardar la imagen.';
            return null;
        }
        return 'uploads/perfiles/' . $nombre;
    }
}

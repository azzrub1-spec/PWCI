<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;
use RuntimeException;

final class ArchivoNivel
{
    private const TYPES = [
        'video' => [
            'mimes' => ['video/mp4' => 'mp4', 'video/webm' => 'webm'],
            'max' => 50 * 1024 * 1024,
            'label' => 'video MP4 o WebM de hasta 50 MB',
        ],
        'pdf' => [
            'mimes' => ['application/pdf' => 'pdf'],
            'max' => 5 * 1024 * 1024,
            'label' => 'archivo PDF de hasta 5 MB',
        ],
        'imagen' => [
            'mimes' => ['image/jpeg' => 'jpg', 'image/png' => 'png'],
            'max' => 2 * 1024 * 1024,
            'label' => 'imagen JPG o PNG de hasta 2 MB',
        ],
    ];

    public function __construct(private string $directory)
    {
    }

    public function upload(string $type): ?string
    {
        $rule = self::TYPES[$type];
        $file = $_FILES[$type] ?? null;

        if ($file === null) {
            return null;
        }

        if (!is_array($file) || !is_int($file['error'] ?? null)) {
            throw new InvalidArgumentException('El archivo recibido no es válido.');
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if (in_array($file['error'], [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true)) {
            throw new InvalidArgumentException(
                'El archivo supera el límite de PHP. Revisa los límites de carga '
                . 'indicados en el README y usa un ' . $rule['label'] . '.'
            );
        }

        if ($file['error'] !== UPLOAD_ERR_OK
            || !is_string($file['tmp_name'] ?? null)
            || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('No se pudo recibir el archivo.');
        }

        $size = filesize($file['tmp_name']);
        if ($size === false || $size < 1 || $size > $rule['max']) {
            throw new InvalidArgumentException('Selecciona un ' . $rule['label'] . '.');
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!isset($rule['mimes'][$mime])) {
            throw new InvalidArgumentException('Selecciona un ' . $rule['label'] . '.');
        }

        if ($type === 'imagen') {
            $info = @getimagesize($file['tmp_name']);
            if ($info === false || $info['mime'] !== $mime) {
                throw new InvalidArgumentException('La imagen no es válida.');
            }
        }

        if (!is_dir($this->directory)
            && !@mkdir($this->directory, 0755, true) && !is_dir($this->directory)) {
            throw new RuntimeException('No se pudo preparar la carpeta de materiales.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $rule['mimes'][$mime];
        if (!move_uploaded_file($file['tmp_name'], $this->directory . '/' . $name)) {
            throw new RuntimeException('No se pudo guardar el material del nivel.');
        }

        return $name;
    }

    public function exists(?string $name, string $type): bool
    {
        if ($name === null || !isset(self::TYPES[$type])) {
            return false;
        }

        $extensions = implode('|', array_values(self::TYPES[$type]['mimes']));

        return preg_match('~\A[a-f0-9]{32}\.(' . $extensions . ')\z~', $name) === 1
            && is_file($this->directory . '/' . $name);
    }

    public function remove(string $name): void
    {
        if (preg_match('/\A[a-f0-9]{32}\.(mp4|webm|pdf|jpg|png)\z/', $name) === 1) {
            @unlink($this->directory . '/' . $name);
        }
    }

    public function send(string $name, string $type): never
    {
        if (!$this->exists($name, $type)) {
            throw new InvalidArgumentException('Material no encontrado.');
        }

        $file = $this->directory . '/' . $name;
        $handle = fopen($file, 'rb');
        if ($handle === false) {
            throw new RuntimeException('No se pudo abrir el material.');
        }

        $size = (int) (fstat($handle)['size'] ?? 0);
        if ($size < 1) {
            fclose($handle);
            throw new InvalidArgumentException('El material está vacío.');
        }
        $start = 0;
        $end = $size - 1;
        $range = $_SERVER['HTTP_RANGE'] ?? '';

        // Permite adelantar y retroceder el video sin descargarlo completo.
        if ($type === 'video' && $range !== '') {
            if (!preg_match('/\Abytes=(\d*)-(\d*)\z/', $range, $match)
                || ($match[1] === '' && $match[2] === '')) {
                $this->invalidRange($size);
            }

            if ($match[1] === '') {
                $start = max(0, $size - (int) $match[2]);
            } else {
                $start = (int) $match[1];
                if ($match[2] !== '') {
                    $end = min($end, (int) $match[2]);
                }
            }

            if ($start > $end || $start >= $size) {
                $this->invalidRange($size);
            }
            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        }

        $extension = pathinfo($name, PATHINFO_EXTENSION);
        $mime = array_search($extension, self::TYPES[$type]['mimes'], true);
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . ($end - $start + 1));
        header('Content-Disposition: inline; filename="material.' . $extension . '"');
        header('Cache-Control: private, no-store');
        header('X-Content-Type-Options: nosniff');
        if ($type === 'video') {
            header('Accept-Ranges: bytes');
        }

        session_write_close();
        fseek($handle, $start);
        $remaining = $end - $start + 1;

        while ($remaining > 0 && !feof($handle) && !connection_aborted()) {
            $chunk = fread($handle, min(65536, $remaining));
            if ($chunk === false || $chunk === '') {
                break;
            }
            echo $chunk;
            $remaining -= strlen($chunk);
        }

        fclose($handle);
        exit;
    }

    private function invalidRange(int $size): never
    {
        http_response_code(416);
        header('Content-Range: bytes */' . $size);
        exit;
    }
}

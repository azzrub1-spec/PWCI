<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Usuario;

final class ProfileController
{
    public function __construct(private Usuario $usuarios)
    {
    }

    public function show(): void
    {
        AuthMiddleware::requireSession();
        $usuario = $this->usuarios->findById((int) $_SESSION['usuario']['id']);
        View::render('perfil/index', ['usuario' => $usuario]);
    }

    public function update(): void
    {
        AuthMiddleware::requireSession();
        $id = (int) $_SESSION['usuario']['id'];
        $usuario = $this->usuarios->findById($id);
        $data = [
            'nombre' => trim((string) ($_POST['nombre'] ?? '')),
            'apellidos' => trim((string) ($_POST['apellidos'] ?? '')),
            'genero' => (string) ($_POST['genero'] ?? ''),
            'fecha_nacimiento' => (string) ($_POST['fecha_nacimiento'] ?? ''),
            'imagen_perfil' => $usuario['imagen_perfil'],
        ];
        $errors = $this->validate($data);

        if ($_POST === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $errors = ['El formulario supera el límite del servidor. Usa una imagen de hasta 2 MB.'];
        }

        $ruta = null;
        if ($errors === []) {
            $ruta = $this->guardarImagen($errors);
            if ($ruta !== null) {
                $data['imagen_perfil'] = $ruta;
            }
            if ($errors === [] && !$this->esImagenLocal($data['imagen_perfil'])) {
                $errors[] = 'Selecciona una imagen JPG o PNG para tu perfil.';
            }
        }

        if ($errors !== []) {
            $usuario = array_merge(
                $this->usuarios->findById((int) $_SESSION['usuario']['id']),
                $data
            );
            View::render('perfil/index', [
                'usuario' => $usuario,
                'errors' => $errors,
            ]);
            return;
        }

        try {
            $this->usuarios->updateProfile($id, $data);
        } catch (\Throwable $exception) {
            if ($ruta !== null) {
                @unlink(dirname(__DIR__, 2) . '/public/' . $ruta);
            }
            View::render('perfil/index', [
                'usuario' => $usuario,
                'errors' => ['No se pudieron guardar los cambios. Intenta nuevamente.'],
            ]);
            return;
        }

        // Retirar únicamente la fotografía local anterior del mismo usuario.
        if ($ruta !== null && $this->esImagenLocal($usuario['imagen_perfil'])) {
            @unlink(dirname(__DIR__, 2) . '/public/' . $usuario['imagen_perfil']);
        }
        $_SESSION['usuario']['nombre'] = $data['nombre'];
        View::render('perfil/index', [
            'usuario' => $this->usuarios->findById((int) $_SESSION['usuario']['id']),
            'success' => 'Perfil actualizado correctamente.',
        ]);
    }

    private function validate(array $data): array
    {
        $errors = [];

        if ($data['nombre'] === '' || $data['apellidos'] === '') {
            $errors[] = 'Nombre y apellidos son obligatorios.';
        }

        if (!in_array($data['genero'], ['Femenino', 'Masculino', 'Otro'], true)) {
            $errors[] = 'Selecciona un género válido.';
        }

        $date = \DateTimeImmutable::createFromFormat(
            '!Y-m-d',
            $data['fecha_nacimiento']
        );
        if ($date === false || $date->format('Y-m-d') !== $data['fecha_nacimiento']
            || $date > new \DateTimeImmutable('today')) {
            $errors[] = 'La fecha de nacimiento no es válida.';
        }

        if (mb_strlen($data['nombre']) > 100 || mb_strlen($data['apellidos']) > 150) {
            $errors[] = 'El nombre o los apellidos superan la longitud permitida.';
        }

        return $errors;
    }

    private function esImagenLocal(?string $ruta): bool
    {
        return preg_match(
            '~\Auploads/perfiles/[a-f0-9]{32}\.(jpg|png)\z~',
            $ruta ?? ''
        ) === 1 && is_file(dirname(__DIR__, 2) . '/public/' . $ruta);
    }

    private function guardarImagen(array &$errors): ?string
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


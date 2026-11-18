<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\CategoriaRepository;
use App\Contracts\CursoRepository;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use InvalidArgumentException;
use PDOException;
use RuntimeException;
use Throwable;

final class CursoController
{
    public function __construct(
        private CursoRepository $cursos,
        private CategoriaRepository $categorias
    ) {
    }

    public function index(): void
    {
        View::render('instructor/cursos', [
            'csrfToken' => AuthMiddleware::csrfToken(),
        ]);
    }

    public function api(string $method, ?int $id, bool $owned): never
    {
        if ($method === 'GET') {
            if ($owned) {
                $instructor = (int) $_SESSION['usuario']['id'];
                if ($id !== null) {
                    $this->respondCourse($this->cursos->findOwned($id, $instructor));
                }

                $items = $this->cursos->byInstructor($instructor);
                $this->respond(200, [
                    'success' => true,
                    'total' => count($items),
                    'cursos' => $items,
                    'csrf_token' => AuthMiddleware::csrfToken(),
                ]);
            }

            if ($id !== null) {
                $this->respondCourse($this->cursos->findActive($id));
            }

            try {
                $filters = $this->catalogFilters($_GET);
            } catch (InvalidArgumentException $exception) {
                $this->respond(400, ['success' => false, 'message' => $exception->getMessage()]);
            }
            $items = $this->cursos->active($filters);
            $this->respond(200, ['success' => true, 'total' => count($items), 'cursos' => $items]);
        }

        if ($method === 'POST' && $id === null) {
            $this->save(null);
        }

        if ($id !== null && in_array($method, ['PUT', 'DELETE'], true)) {
            $instructor = (int) $_SESSION['usuario']['id'];
            $curso = $this->cursos->findOwned($id, $instructor);

            if ($curso === null) {
                $this->respond(404, ['success' => false, 'message' => 'Curso no encontrado.']);
            }

            if (!(bool) $curso['activo']) {
                $this->respond(409, ['success' => false, 'message' => 'El curso ya está dado de baja.']);
            }

            if ($method === 'PUT') {
                $this->save($curso);
            }

            if (!$this->cursos->softDelete($id, $instructor)) {
                $this->respond(409, ['success' => false, 'message' => 'El curso ya está dado de baja.']);
            }
            $this->respond(200, ['success' => true, 'id' => $id, 'message' => 'Curso dado de baja.']);
        }

        $this->respond(404, ['success' => false, 'message' => 'Ruta o método no disponible.']);
    }

    private function catalogFilters(array $input): array
    {
        $filters = [];
        foreach (['titulo', 'instructor', 'categoria', 'desde', 'hasta'] as $key) {
            $value = $input[$key] ?? '';
            if (!is_string($value)) {
                throw new InvalidArgumentException('Los filtros deben tener un solo valor.');
            }
            $filters[$key] = trim($value);
        }

        if ($filters['categoria'] !== ''
            && filter_var($filters['categoria'], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
            throw new InvalidArgumentException('La categoría no es válida.');
        }

        foreach (['desde', 'hasta'] as $key) {
            $date = $filters[$key];
            if ($date !== ''
                && (!preg_match('/\A([0-9]{4})-([0-9]{2})-([0-9]{2})\z/', $date, $parts)
                    || (int) $parts[1] < 1000
                    || !checkdate((int) $parts[2], (int) $parts[3], (int) $parts[1]))) {
                throw new InvalidArgumentException('Ingresa fechas de publicación válidas (AAAA-MM-DD).');
            }
        }

        if ($filters['desde'] !== '' && $filters['hasta'] !== '' && $filters['desde'] > $filters['hasta']) {
            throw new InvalidArgumentException('La fecha inicial no puede ser posterior a la final.');
        }

        return $filters;
    }

    private function save(?array $curso): never
    {
        $newImage = null;

        try {
            $body = $this->requestData();
            $data = $this->validate($body);
            $newImage = $this->uploadImage();
            $data['imagen'] = $newImage ?? ($curso['imagen'] ?? '');

            if ($data['imagen'] === '') {
                throw new InvalidArgumentException('Selecciona una imagen JPG o PNG para el curso.');
            }

            $instructor = (int) $_SESSION['usuario']['id'];

            if ($curso === null) {
                $data['id_instructor'] = $instructor;
                $id = $this->cursos->create($data);
            } else {
                $id = (int) $curso['id_curso'];
                $this->cursos->update($id, $instructor, $data);
            }
        } catch (Throwable $exception) {
            // Una carga fallida no debe dejar una imagen nueva sin curso.
            if ($newImage !== null) {
                @unlink(dirname(__DIR__, 2) . '/public/' . $newImage);
            }

            if ($exception instanceof InvalidArgumentException) {
                $this->respond(400, ['success' => false, 'message' => $exception->getMessage()]);
            }

            if ($exception instanceof PDOException
                && (int) ($exception->errorInfo[1] ?? 0) === 1452) {
                $this->respond(400, [
                    'success' => false,
                    'message' => 'La categoría o el usuario ya no existe. Recarga la página.',
                ]);
            }

            throw $exception;
        }

        $this->respond($curso === null ? 201 : 200, [
            'success' => true,
            'id' => $id,
            'message' => $curso === null
                ? 'Curso creado correctamente.'
                : 'Curso actualizado correctamente.',
        ]);
    }

    private function requestData(): array
    {
        $contentType = (string) ($_SERVER['CONTENT_TYPE'] ?? '');

        if (str_starts_with(strtolower($contentType), 'application/json')) {
            $body = json_decode((string) file_get_contents('php://input'));
            if (!is_object($body)) {
                throw new InvalidArgumentException('El cuerpo debe ser un objeto JSON válido.');
            }

            return get_object_vars($body);
        }

        if ($_POST === [] && $_FILES === []) {
            throw new InvalidArgumentException(
                'No se recibió el formulario. Selecciona una imagen de hasta 2 MB '
                . 'y verifica los límites de carga de PHP.'
            );
        }

        return $_POST;
    }

    private function validate(array $body): array
    {
        $title = is_string($body['titulo'] ?? null) ? trim($body['titulo']) : '';
        $description = is_string($body['descripcion'] ?? null) ? trim($body['descripcion']) : '';
        $category = filter_var(
            $body['categoria_id'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        $cost = $body['costo'] ?? null;

        if (!preg_match('/\A[^\r\n]{1,150}\z/u', $title)) {
            throw new InvalidArgumentException('El título es obligatorio y admite hasta 150 caracteres.');
        }

        if ($description === '' || strlen($description) > 65535
            || preg_match('//u', $description) !== 1) {
            throw new InvalidArgumentException('La descripción es obligatoria y admite hasta 65535 bytes.');
        }

        if ($category === false || $this->categorias->find($category) === null) {
            throw new InvalidArgumentException('Selecciona una categoría existente.');
        }

        if ((!is_string($cost) && !is_int($cost) && !is_float($cost))
            || !preg_match('/\A\d{1,8}(?:\.\d{1,2})?\z/', (string) $cost)) {
            throw new InvalidArgumentException(
                'El costo debe estar entre 0 y 99999999.99, con hasta dos decimales.'
            );
        }

        return [
            'titulo' => $title,
            'descripcion' => $description,
            'id_categoria' => $category,
            'costo' => (string) $cost,
        ];
    }

    private function uploadImage(): ?string
    {
        $file = $_FILES['imagen'] ?? null;

        if ($file === null) {
            return null;
        }

        if (!is_array($file) || !is_int($file['error'] ?? null)) {
            throw new InvalidArgumentException('La imagen recibida no es válida.');
        }

        if ($file['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new InvalidArgumentException('No se pudo recibir la imagen. El máximo es 2 MB.');
        }

        if (!is_int($file['size'] ?? null) || $file['size'] <= 0
            || $file['size'] > 2 * 1024 * 1024
            || !is_string($file['tmp_name'] ?? null)
            || !is_uploaded_file($file['tmp_name'])) {
            throw new InvalidArgumentException('Selecciona una imagen válida de hasta 2 MB.');
        }

        $info = @getimagesize($file['tmp_name']);
        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png'];

        if ($info === false || !isset($allowed[$mime]) || $info['mime'] !== $mime) {
            throw new InvalidArgumentException('Solo se admiten imágenes JPG o PNG válidas.');
        }

        $directory = dirname(__DIR__, 2) . '/public/uploads/cursos';
        if (!is_dir($directory) && !@mkdir($directory, 0755, true) && !is_dir($directory)) {
            throw new RuntimeException('No se pudo preparar la carpeta de imágenes.');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
        if (!move_uploaded_file($file['tmp_name'], $directory . '/' . $name)) {
            throw new RuntimeException('No se pudo guardar la imagen.');
        }

        return 'uploads/cursos/' . $name;
    }

    private function respondCourse(?array $curso): never
    {
        if ($curso === null) {
            $this->respond(404, ['success' => false, 'message' => 'Curso no encontrado.']);
        }

        $this->respond(200, ['success' => true, 'curso' => $curso]);
    }

    private function respond(int $status, array $body): never
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

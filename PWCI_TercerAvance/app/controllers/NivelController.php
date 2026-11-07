<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\CursoRepository;
use App\Contracts\NivelRepository;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Services\ArchivoNivel;
use InvalidArgumentException;
use PDOException;
use Throwable;

final class NivelController
{
    public function __construct(
        private NivelRepository $niveles,
        private CursoRepository $cursos,
        private ArchivoNivel $archivos
    ) {
    }

    public function index(): void
    {
        $id = $this->positiveId($_GET['curso'] ?? null);
        $curso = $id === null ? null
            : $this->cursos->findOwned($id, (int) $_SESSION['usuario']['id']);

        if ($curso === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        View::render('instructor/niveles', [
            'curso' => $curso,
            'csrfToken' => AuthMiddleware::csrfToken(),
        ]);
    }

    public function api(string $method, ?int $id): never
    {
        if ($method === 'GET') {
            if ($id !== null) {
                $level = $this->findOwned($id);
                $this->respond(200, ['success' => true, 'nivel' => $level]);
            }

            $courseId = $this->positiveId($_GET['curso_id'] ?? null);
            if ($courseId === null) {
                $this->respond(400, ['success' => false, 'message' => 'El curso no es válido.']);
            }
            $this->requireCourse($courseId);
            $items = $this->niveles->byCourse($courseId);
            $this->respond(200, [
                'success' => true,
                'total' => count($items),
                'niveles' => $items,
                'csrf_token' => AuthMiddleware::csrfToken(),
            ]);
        }

        if ($method === 'POST' && $id === null) {
            $this->save(null);
        }

        if ($id !== null && in_array($method, ['PUT', 'DELETE'], true)) {
            $level = $this->findOwned($id);
            $this->requireCourse((int) $level['id_curso'], true);

            if ($method === 'PUT') {
                $this->save($level);
            }

            if (!$this->niveles->delete($id)) {
                $this->respond(409, [
                    'success' => false,
                    'message' => 'No puedes eliminar niveles de un curso que ya tiene estudiantes inscritos.',
                ]);
            }

            foreach (['video_url', 'archivo_pdf', 'imagen'] as $column) {
                if (!empty($level[$column])) {
                    $this->archivos->remove($level[$column]);
                }
            }
            $this->respond(200, ['success' => true, 'message' => 'Nivel eliminado correctamente.']);
        }

        $this->respond(404, ['success' => false, 'message' => 'Ruta o método no disponible.']);
    }

    public function file(): void
    {
        $id = $this->positiveId($_GET['id'] ?? null);
        $type = $_GET['tipo'] ?? '';
        $columns = ['video' => 'video_url', 'pdf' => 'archivo_pdf', 'imagen' => 'imagen'];
        $level = $id === null ? null : $this->niveles->find($id);
        $course = $level === null ? null : $this->cursos->findOwned(
            (int) $level['id_curso'],
            (int) $_SESSION['usuario']['id']
        );

        if ($course === null || !is_string($type) || !isset($columns[$type])
            || !$this->archivos->exists($level[$columns[$type]] ?? null, $type)) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $this->archivos->send($level[$columns[$type]], $type);
    }

    private function save(?array $level): never
    {
        $newFiles = [];
        $columns = ['video' => 'video_url', 'pdf' => 'archivo_pdf', 'imagen' => 'imagen'];

        try {
            $body = $this->requestData();
            $courseId = $level === null
                ? $this->positiveId($body['curso_id'] ?? null)
                : (int) $level['id_curso'];
            if ($courseId === null) {
                throw new InvalidArgumentException('Selecciona un curso válido.');
            }

            $this->requireCourse($courseId, true);
            $data = $this->validate($body);
            $data['id_curso'] = $courseId;

            foreach ($columns as $type => $column) {
                $new = $this->archivos->upload($type);
                if ($new !== null) {
                    $newFiles[] = $new;
                }
                $data[$column] = $new ?? ($level[$column] ?? null);

                if ($type !== 'video' && $new === null
                    && in_array($body['quitar_' . $type] ?? null, ['1', 1], true)) {
                    $data[$column] = null;
                }
            }

            if (!$this->archivos->exists($data['video_url'], 'video')) {
                throw new InvalidArgumentException('Cada nivel debe tener un video MP4 o WebM.');
            }

            if ($level === null) {
                $id = $this->niveles->create($data);
            } else {
                $id = (int) $level['id_nivel'];
                $this->niveles->update($id, $data);
            }
        } catch (Throwable $exception) {
            foreach ($newFiles as $name) {
                $this->archivos->remove($name);
            }

            if ($exception instanceof InvalidArgumentException) {
                $this->respond(400, ['success' => false, 'message' => $exception->getMessage()]);
            }

            if ($exception instanceof PDOException) {
                $code = (int) ($exception->errorInfo[1] ?? 0);
                if ($code === 1062) {
                    $this->respond(409, [
                        'success' => false,
                        'message' => 'Ya existe un nivel con ese orden en este curso.',
                    ]);
                }
                if ($code === 1054) {
                    $this->respond(500, [
                        'success' => false,
                        'message' => 'Falta actualizar la tabla nivel. Ejecuta database/03_niveles_contenido.sql.',
                    ]);
                }
            }

            throw $exception;
        }

        if ($level !== null) {
            foreach ($columns as $column) {
                if (!empty($level[$column]) && $level[$column] !== $data[$column]) {
                    $this->archivos->remove($level[$column]);
                }
            }
        }

        $this->respond($level === null ? 201 : 200, [
            'success' => true,
            'id' => $id,
            'message' => $level === null ? 'Nivel creado correctamente.' : 'Nivel actualizado correctamente.',
        ]);
    }

    private function validate(array $body): array
    {
        $title = is_string($body['titulo'] ?? null) ? trim($body['titulo']) : '';
        $order = $this->positiveId($body['orden'] ?? null);
        $cost = $body['costo_nivel'] ?? null;
        $text = is_string($body['contenido_texto'] ?? '') ? trim($body['contenido_texto'] ?? '') : null;
        $link = is_string($body['enlace'] ?? '') ? trim($body['enlace'] ?? '') : null;

        if (!preg_match('/\A[^\r\n]{1,150}\z/u', $title)) {
            throw new InvalidArgumentException('El título es obligatorio y admite hasta 150 caracteres.');
        }
        if ($order === null || $order > 4294967295) {
            throw new InvalidArgumentException('El orden debe ser un entero entre 1 y 4294967295.');
        }
        if ((!is_string($cost) && !is_int($cost) && !is_float($cost))
            || !preg_match('/\A\d{1,8}(?:\.\d{1,2})?\z/', (string) $cost)) {
            throw new InvalidArgumentException('El costo debe ser de 0 a 99999999.99, con hasta dos decimales.');
        }
        if ($text === null || strlen($text) > 65535 || preg_match('//u', $text) !== 1) {
            throw new InvalidArgumentException('El texto del nivel admite hasta 65535 bytes UTF-8.');
        }
        if ($link === null || ($link !== ''
            && (strlen($link) > 2048 || !filter_var($link, FILTER_VALIDATE_URL)
                || !in_array(strtolower((string) parse_url($link, PHP_URL_SCHEME)), ['http', 'https'], true)))) {
            throw new InvalidArgumentException('El enlace debe ser una dirección HTTP o HTTPS válida.');
        }

        return [
            'titulo' => $title,
            'orden' => $order,
            'costo_nivel' => (string) $cost,
            'gratis' => (float) $cost === 0.0 ? 1 : 0,
            'contenido_texto' => $text,
            'enlace' => $link === '' ? null : $link,
        ];
    }

    private function requestData(): array
    {
        if (str_starts_with(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'application/json')) {
            $body = json_decode((string) file_get_contents('php://input'));
            if (!is_object($body)) {
                throw new InvalidArgumentException('El cuerpo debe ser un objeto JSON válido.');
            }
            return get_object_vars($body);
        }

        return $_POST;
    }

    private function positiveId(mixed $value): ?int
    {
        $id = filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);

        return $id === false ? null : $id;
    }

    private function requireCourse(int $id, bool $editing = false): array
    {
        $course = $this->cursos->findOwned($id, (int) $_SESSION['usuario']['id']);
        if ($course === null) {
            $this->respond(404, ['success' => false, 'message' => 'Curso no encontrado.']);
        }
        if ($editing && !(bool) $course['activo']) {
            $this->respond(409, ['success' => false, 'message' => 'El curso está dado de baja.']);
        }

        return $course;
    }

    private function findOwned(int $id): array
    {
        $level = $this->niveles->find($id);
        if ($level === null) {
            $this->respond(404, ['success' => false, 'message' => 'Nivel no encontrado.']);
        }
        $this->requireCourse((int) $level['id_curso']);

        return $level;
    }

    private function respond(int $status, array $body): never
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

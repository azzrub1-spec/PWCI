<?php

declare(strict_types=1);

use App\Controllers\CategoriaController;
use App\Controllers\CursoController;
use App\Controllers\EstudianteController;
use App\Controllers\NivelController;
use App\Core\Database;
use App\Middleware\AuthMiddleware;
use App\Middleware\CorsMiddleware;
use App\Models\Categoria;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\ArchivoNivel;

require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
CorsMiddleware::handle();

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

try {
    $method = $_SERVER['REQUEST_METHOD'];
    $path = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
    $parts = $path === '' ? [] : explode('/', $path);
    $resource = $parts[0] ?? '';

    $studentResource = in_array($resource, ['inscripciones', 'progreso'], true);
    if ((!$studentResource && !in_array($resource, ['categorias', 'cursos', 'niveles'], true)) || count($parts) > 2) {
        respond(404, ['success' => false, 'message' => 'Recurso no encontrado.']);
    }

    $id = isset($parts[1])
        ? filter_var($parts[1], FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])
        : null;

    if ($id === false) {
        respond(400, ['success' => false, 'message' => 'El identificador no es válido.']);
    }

    // PHP recibe los archivos de formularios multipart por POST.
    // En edición, _method=PUT permite conservar la imagen o reemplazarla.
    if (in_array($resource, ['cursos', 'niveles'], true)
        && $method === 'POST' && isset($_POST['_method'])) {
        if ($_POST['_method'] !== 'PUT') {
            respond(400, ['success' => false, 'message' => 'Método de formulario no válido.']);
        }
        $method = 'PUT';
    }

    $owned = $resource === 'cursos' && ($_GET['propios'] ?? '') === '1';

    if ($resource === 'categorias' && $method !== 'GET') {
        AuthMiddleware::requireApiRole(['administrador']);
        AuthMiddleware::requireCsrf();
    }

    if ($resource === 'cursos') {
        if ($owned || $method !== 'GET') {
            AuthMiddleware::requireApiRole(['instructor']);
        }
        if ($method !== 'GET') {
            AuthMiddleware::requireCsrf();
        }
    }

    if ($resource === 'niveles') {
        AuthMiddleware::requireApiRole(['instructor']);
        if ($method !== 'GET') {
            AuthMiddleware::requireCsrf();
        }
    }

    if ($method === 'POST' && $_POST === [] && $_FILES === []
        && str_starts_with(strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? '')), 'multipart/form-data')
        && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
        respond(400, [
            'success' => false,
            'message' => 'El formulario supera el límite de carga de PHP. Revisa post_max_size en el README.',
        ]);
    }

    if ($studentResource) {
        AuthMiddleware::requireApiRole(['estudiante']);
        if ($method !== 'GET') {
            AuthMiddleware::requireCsrf();
        }
    }

    $database = new Database();
    if ($studentResource) {
        $controller = new EstudianteController(
            new Inscripcion($database->connection()),
            new Curso($database->connection()),
            new Nivel($database->connection()),
            new ArchivoNivel(require dirname(__DIR__) . '/config/archivos.php')
        );
        $controller->api($resource, $method, $id);
    }
    $categories = new Categoria($database->connection());

    if ($resource === 'categorias') {
        $controller = new CategoriaController($categories);
        $controller->api($method, $id);
    }

    if ($resource === 'niveles') {
        $controller = new NivelController(
            new Nivel($database->connection()),
            new Curso($database->connection()),
            new ArchivoNivel(require dirname(__DIR__) . '/config/archivos.php')
        );
        $controller->api($method, $id);
    }

    $controller = new CursoController(new Curso($database->connection()), $categories);
    $controller->api($method, $id, $owned);
} catch (Throwable $exception) {
    respond(500, [
        'success' => false,
        'message' => 'Ocurrió un error interno. Intenta nuevamente.',
    ]);
}


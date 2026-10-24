<?php

declare(strict_types=1);

use App\Core\Database;
use App\Middleware\CorsMiddleware;
use App\Models\Curso;

require dirname(__DIR__) . '/bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
CorsMiddleware::handle();

function respond(int $status, array $body): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function requestBody(): array
{
    $body = json_decode((string) file_get_contents('php://input'), true);

    if (!is_array($body)) {
        respond(400, [
            'success' => false,
            'message' => 'El cuerpo debe contener JSON válido.',
        ]);
    }

    return $body;
}

try {
    $database = new Database();
    $cursos = new Curso($database->connection());
    $method = $_SERVER['REQUEST_METHOD'];
    $path = trim((string) ($_SERVER['PATH_INFO'] ?? ''), '/');
    $parts = $path === '' ? [] : explode('/', $path);

    if (($parts[0] ?? '') !== 'cursos') {
        respond(404, ['success' => false, 'message' => 'Recurso no encontrado.']);
    }

    $id = isset($parts[1]) && ctype_digit($parts[1]) ? (int) $parts[1] : null;

    if (isset($parts[1]) && $id === null) {
        respond(400, ['success' => false, 'message' => 'El identificador no es válido.']);
    }

    if ($method === 'GET' && $id === null) {
        $items = $cursos->active([
            'titulo' => trim((string) ($_GET['titulo'] ?? '')),
            'categoria' => $_GET['categoria'] ?? null,
        ]);
        respond(200, ['success' => true, 'total' => count($items), 'cursos' => $items]);
    }

    if ($method === 'GET' && $id !== null) {
        $item = $cursos->findActive($id);
        if ($item === null) {
            respond(404, ['success' => false, 'message' => 'Curso no encontrado.']);
        }
        respond(200, ['success' => true, 'curso' => $item]);
    }

    if ($method === 'POST' && $id === null) {
        if (empty($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'instructor') {
            respond(401, ['success' => false, 'message' => 'Se requiere una sesión de instructor.']);
        }

        $body = requestBody();
        $title = trim((string) ($body['titulo'] ?? ''));
        $description = trim((string) ($body['descripcion'] ?? ''));
        $category = filter_var($body['categoria_id'] ?? null, FILTER_VALIDATE_INT);
        $cost = filter_var($body['costo'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($title === '' || $description === '' || $category === false
            || $category < 1
            || $cost === false || $cost < 0) {
            respond(400, ['success' => false, 'message' => 'Los datos del curso no son válidos.']);
        }

        $newId = $cursos->create([
            'id_instructor' => $_SESSION['usuario']['id'],
            'id_categoria' => $category,
            'titulo' => $title,
            'descripcion' => $description,
            'imagen' => trim((string) ($body['imagen'] ?? '')),
            'costo' => $cost,
        ]);
        respond(201, ['success' => true, 'id' => $newId, 'message' => 'Curso creado correctamente.']);
    }

    if ($method === 'DELETE' && $id !== null) {
        if (empty($_SESSION['usuario']) || $_SESSION['usuario']['rol'] !== 'instructor') {
            respond(401, ['success' => false, 'message' => 'Se requiere una sesión de instructor.']);
        }

        if (!$cursos->softDelete($id, (int) $_SESSION['usuario']['id'])) {
            respond(404, ['success' => false, 'message' => 'Curso no encontrado.']);
        }
        respond(200, ['success' => true, 'id' => $id, 'message' => 'Curso dado de baja.']);
    }

    respond(404, ['success' => false, 'message' => 'Ruta o método no disponible.']);
} catch (Throwable $exception) {
    respond(500, [
        'success' => false,
        'message' => 'Ocurrió un error interno. Intenta nuevamente.',
    ]);
}


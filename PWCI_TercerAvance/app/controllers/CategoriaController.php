<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\CategoriaRepository;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use PDOException;

final class CategoriaController
{
    public function __construct(private CategoriaRepository $categorias)
    {
    }

    public function index(): void
    {
        View::render('categorias/index', [
            'csrfToken' => AuthMiddleware::csrfToken(),
        ]);
    }

    public function api(string $method, ?int $id): never
    {
        try {
            if ($method === 'GET' && $id === null) {
                $response = [
                    'success' => true,
                    'categorias' => $this->categorias->all(),
                ];

                if (($_SESSION['usuario']['rol'] ?? '') === 'administrador') {
                    $response['csrf_token'] = AuthMiddleware::csrfToken();
                }

                $this->respond(200, $response);
            }

            if ($id !== null && $this->categorias->find($id) === null) {
                $this->respond(404, [
                    'success' => false,
                    'message' => 'Categoría no encontrada.',
                ]);
            }

            if ($method === 'DELETE' && $id !== null) {
                if (!$this->categorias->delete($id)) {
                    $this->respond(404, [
                        'success' => false,
                        'message' => 'Categoría no encontrada.',
                    ]);
                }

                $this->respond(200, [
                    'success' => true,
                    'message' => 'Categoría eliminada correctamente.',
                ]);
            }

            if (($method === 'POST' && $id === null)
                || ($method === 'PUT' && $id !== null)) {
                $body = json_decode((string) file_get_contents('php://input'), true);

                if (!is_array($body)
                    || !is_string($body['nombre'] ?? null)
                    || !is_string($body['descripcion'] ?? '')) {
                    $this->respond(400, [
                        'success' => false,
                        'message' => 'Envía un objeto JSON con nombre y descripción.',
                    ]);
                }

                $nombre = trim($body['nombre']);
                $descripcion = trim($body['descripcion'] ?? '');

                if (!preg_match('/\A[^\r\n]{1,100}\z/u', $nombre)
                    || strlen($descripcion) > 65535) {
                    $this->respond(400, [
                        'success' => false,
                        'message' => 'El nombre es obligatorio y admite hasta 100 caracteres. '
                            . 'La descripción admite hasta 65535 bytes.',
                    ]);
                }

                if ($method === 'POST') {
                    $id = $this->categorias->create(
                        $nombre,
                        $descripcion,
                        (int) $_SESSION['usuario']['id']
                    );
                } else {
                    $this->categorias->update($id, $nombre, $descripcion);
                }

                $this->respond($method === 'POST' ? 201 : 200, [
                    'success' => true,
                    'id' => $id,
                    'message' => $method === 'POST'
                        ? 'Categoría creada correctamente.'
                        : 'Categoría actualizada correctamente.',
                ]);
            }

            $this->respond(404, [
                'success' => false,
                'message' => 'Ruta o método no disponible.',
            ]);
        } catch (PDOException $exception) {
            $code = (int) ($exception->errorInfo[1] ?? 0);

            if ($code === 1062 || $code === 1451) {
                $this->respond(409, [
                    'success' => false,
                    'message' => $code === 1062
                        ? 'Ya existe una categoría con ese nombre.'
                        : 'No puedes eliminar una categoría que tiene cursos asociados.',
                ]);
            }

            throw $exception;
        }
    }

    private function respond(int $status, array $body): never
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

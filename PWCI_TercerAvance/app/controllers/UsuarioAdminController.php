<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Usuario;

final class UsuarioAdminController
{
    public function __construct(private Usuario $usuarios)
    {
    }

    public function index(): void
    {
        AuthMiddleware::requireRole(['administrador']);
        $aviso = $_SESSION['aviso_usuarios'] ?? null;
        unset($_SESSION['aviso_usuarios']);

        View::render('admin/usuarios', [
            'usuarios' => $this->usuarios->listForAdministration(),
            'csrf' => AuthMiddleware::csrfToken(),
            'aviso' => $aviso,
        ]);
    }

    public function report(): void
    {
        AuthMiddleware::requireRole(['administrador']);
        $rol = $_GET['rol'] ?? 'instructor';
        if (!in_array($rol, ['instructor', 'estudiante'], true)) {
            http_response_code(400);
            View::render('admin/reporte-usuarios', [
                'rol' => 'instructor',
                'filas' => [],
                'error' => 'Elige instructores o estudiantes para consultar el reporte.',
            ]);
            return;
        }

        View::render('admin/reporte-usuarios', [
            'rol' => $rol,
            'filas' => $rol === 'instructor'
                ? $this->usuarios->activeInstructorReport()
                : $this->usuarios->activeStudentReport(),
            'error' => null,
        ]);
    }

    public function updateStatus(): void
    {
        AuthMiddleware::requireRole(['administrador']);
        $token = $_POST['csrf'] ?? null;
        if (!is_string($token) || !hash_equals(AuthMiddleware::csrfToken(), $token)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $id = filter_var($_POST['usuario'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $estado = $_POST['activo'] ?? null;
        $administrator = (int) $_SESSION['usuario']['id'];
        if (!$id || !in_array($estado, ['0', '1'], true)) {
            $this->back('La solicitud no es válida.', false);
        }
        if ($id === $administrator) {
            $this->back('No puedes bloquear tu propia cuenta.', false);
        }

        $active = $estado === '1';
        if (!$this->usuarios->setActive($id, $administrator, $active)) {
            $this->back('La cuenta no existe o ya tiene ese estado.', false);
        }

        $this->back($active ? 'Cuenta desbloqueada.' : 'Cuenta bloqueada.', true);
    }

    private function back(string $message, bool $success): never
    {
        $_SESSION['aviso_usuarios'] = ['message' => $message, 'success' => $success];
        header('Location: ' . BASE_URL . '/index.php?r=usuarios-admin', true, 303);
        exit;
    }
}

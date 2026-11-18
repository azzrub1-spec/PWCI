<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Models\Usuario;

final class AuthMiddleware
{
    public static function invalidateInactiveSession(Usuario $usuarios): bool
    {
        if (empty($_SESSION['usuario'])) {
            return false;
        }

        $state = $usuarios->accessState((int) $_SESSION['usuario']['id']);
        if ($state !== null && (bool) $state['activo']) {
            $_SESSION['usuario']['rol'] = $state['rol'];
            return false;
        }

        $_SESSION = [];
        session_regenerate_id(true);
        return true;
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function requireApiRole(array $roles): void
    {
        if (empty($_SESSION['usuario'])) {
            self::apiError(401, 'Debes iniciar sesión.');
        }

        if (!in_array($_SESSION['usuario']['rol'], $roles, true)) {
            self::apiError(403, 'No tienes permiso para realizar esta operación.');
        }
    }

    public static function requireCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

        if ($token === '' || empty($_SESSION['csrf_token'])
            || !hash_equals($_SESSION['csrf_token'], $token)) {
            self::apiError(403, 'La sesión del formulario no es válida. Recarga la página.');
        }
    }

    private static function apiError(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }

    public static function requireSession(): void
    {
        if (empty($_SESSION['usuario'])) {
            header('Location: ' . BASE_URL . '/index.php?r=login');
            exit;
        }
    }

    /** @param string[] $roles */
    public static function requireRole(array $roles): void
    {
        self::requireSession();

        if (!in_array($_SESSION['usuario']['rol'], $roles, true)) {
            http_response_code(403);
            require dirname(__DIR__) . '/views/errors/403.php';
            exit;
        }
    }
}


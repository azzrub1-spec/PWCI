<?php

declare(strict_types=1);

namespace App\Middleware;

final class AuthMiddleware
{
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


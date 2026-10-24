<?php

declare(strict_types=1);

namespace App\Middleware;

final class CorsMiddleware
{
    public static function handle(): void
    {
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

        if ($origin !== '' && !in_array($origin, ALLOWED_ORIGINS, true)) {
            self::error(403, 'Origen no autorizado.');
        }

        if (in_array($origin, ALLOWED_ORIGINS, true)) {
            header('Access-Control-Allow-Origin: ' . $origin);
            header('Vary: Origin');
        }

        header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Accept');
        header('Access-Control-Allow-Credentials: true');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(204);
            exit;
        }
    }

    private static function error(int $status, string $message): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => $message]);
        exit;
    }
}


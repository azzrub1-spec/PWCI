<?php

declare(strict_types=1);

namespace App\Core;

use RuntimeException;

final class View
{
    /** @param array<string, mixed> $data */
    public static function render(string $view, array $data = []): void
    {
        $path = dirname(__DIR__) . '/views/' . $view . '.php';

        if (!is_file($path)) {
            throw new RuntimeException('La vista solicitada no existe.');
        }

        extract($data, EXTR_SKIP);
        require $path;
    }
}


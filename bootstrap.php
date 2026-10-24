<?php

declare(strict_types=1);

require __DIR__ . '/config/config.php';

spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';

    if (!str_starts_with($class, $prefix)) {
        return;
    }

    $parts = explode('\\', substr($class, strlen($prefix)));
    $parts[0] = strtolower($parts[0]);
    $file = __DIR__ . '/app/'
        . implode(DIRECTORY_SEPARATOR, $parts)
        . '.php';

    if (is_file($file)) {
        require $file;
    }
});

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}


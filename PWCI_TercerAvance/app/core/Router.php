<?php

declare(strict_types=1);

namespace App\Core;

final class Router
{
    /** @var array<string, callable> */
    private array $routes = [];

    public function get(string $route, callable $handler): void
    {
        $this->routes['GET:' . $route] = $handler;
    }

    public function post(string $route, callable $handler): void
    {
        $this->routes['POST:' . $route] = $handler;
    }

    public function dispatch(string $method, string $route): void
    {
        $key = strtoupper($method) . ':' . $route;

        if (!isset($this->routes[$key])) {
            http_response_code(404);
            require dirname(__DIR__) . '/views/errors/404.php';
            return;
        }

        ($this->routes[$key])();
    }
}


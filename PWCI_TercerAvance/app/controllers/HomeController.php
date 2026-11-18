<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Middleware\AuthMiddleware;

final class HomeController
{
    public function home(): void
    {
        View::render('publico/inicio');
    }

    public function catalog(): void
    {
        View::render('publico/catalogo');
    }

    public function panel(): void
    {
        AuthMiddleware::requireSession();
        View::render('panel/index', ['usuario' => $_SESSION['usuario']]);
    }
}


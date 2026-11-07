<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\CursoController;
use App\Controllers\HomeController;
use App\Controllers\PaymentController;
use App\Controllers\ProfileController;
use App\Core\Database;
use App\Core\Router;
use App\Models\Usuario;
use App\Models\Categoria;
use App\Models\Curso;
use App\Middleware\AuthMiddleware;

require dirname(__DIR__) . '/bootstrap.php';

$database = new Database();
$usuarios = new Usuario($database->connection());
$auth = new AuthController($usuarios);
$home = new HomeController();
$profile = new ProfileController($usuarios);
$payment = new PaymentController(dirname(__DIR__) . '/config/paypal.local.php');
$router = new Router();
$categorias = new CategoriaController(new Categoria($database->connection()));
$cursos = new CursoController(
    new Curso($database->connection()),
    new Categoria($database->connection())
);

$router->get('inicio', [$home, 'home']);
$router->get('catalogo', [$home, 'catalog']);
$router->get('login', [$auth, 'loginForm']);
$router->post('login', [$auth, 'login']);
$router->get('registro', [$auth, 'registerForm']);
$router->post('registro', [$auth, 'register']);
$router->get('panel', [$home, 'panel']);
$router->get('categorias', static function () use ($categorias): void {
    AuthMiddleware::requireRole(['administrador']);
    $categorias->index();
});
$router->get('perfil', [$profile, 'show']);
$router->get('mis-cursos', static function () use ($cursos): void {
    AuthMiddleware::requireRole(['instructor']);
    $cursos->index();
});
$router->post('perfil', [$profile, 'update']);
$router->get('pago', [$payment, 'show']);
$router->post('paypal-crear', [$payment, 'create']);
$router->get('paypal-capturar', [$payment, 'capture']);
$router->get('paypal-cancelar', [$payment, 'cancel']);
$router->post('logout', [$auth, 'logout']);

$route = trim((string) ($_GET['r'] ?? 'inicio'));
$router->dispatch($_SERVER['REQUEST_METHOD'], $route);


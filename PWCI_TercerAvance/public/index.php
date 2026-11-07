<?php

declare(strict_types=1);

use App\Controllers\AuthController;
use App\Controllers\CategoriaController;
use App\Controllers\CursoController;
use App\Controllers\EstudianteController;
use App\Controllers\NivelController;
use App\Controllers\HomeController;
use App\Controllers\PaymentController;
use App\Controllers\ProfileController;
use App\Controllers\ResenaController;
use App\Controllers\UsuarioAdminController;
use App\Controllers\VentasController;
use App\Core\Database;
use App\Core\Router;
use App\Core\View;
use App\Models\Usuario;
use App\Models\Categoria;
use App\Models\Curso;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Resena;
use App\Models\Venta;
use App\Services\ArchivoNivel;
use App\Middleware\AuthMiddleware;

require dirname(__DIR__) . '/bootstrap.php';

$database = new Database();
$usuarios = new Usuario($database->connection());
if (AuthMiddleware::invalidateInactiveSession($usuarios)) {
    http_response_code(403);
    View::render('auth/login', ['error' => 'La cuenta está bloqueada. Solicita ayuda al administrador.']);
    exit;
}
$usuariosAdmin = new UsuarioAdminController($usuarios);
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
$niveles = new NivelController(
    new Nivel($database->connection()),
    new Curso($database->connection()),
    new ArchivoNivel(require dirname(__DIR__) . '/config/archivos.php')
);
$resenas = new Resena($database->connection());
$resenaController = new ResenaController($resenas);
$estudiante = new EstudianteController(
    new Inscripcion($database->connection()),
    new Curso($database->connection()),
    new Nivel($database->connection()),
    new ArchivoNivel(require dirname(__DIR__) . '/config/archivos.php'),
    $resenas
);

$router->post('resena-publicar', [$resenaController, 'publish']);
$router->post('comentario-moderar', [$resenaController, 'moderate']);
$router->get('inicio', [$home, 'home']);
$router->get('catalogo', [$home, 'catalog']);
$router->get('curso', [$estudiante, 'detail']);
$router->get('mis-inscripciones', static function () use ($estudiante): void {
    AuthMiddleware::requireRole(['estudiante']);
    $estudiante->index();
});
$router->get('nivel-estudiante', static function () use ($estudiante): void {
    AuthMiddleware::requireRole(['estudiante']);
    $estudiante->lesson();
});
$router->get('archivo-estudiante', static function () use ($estudiante): void {
    AuthMiddleware::requireRole(['estudiante']);
    $estudiante->file();
});
$router->get('login', [$auth, 'loginForm']);
$router->post('login', [$auth, 'login']);
$router->get('registro', [$auth, 'registerForm']);
$router->post('registro', [$auth, 'register']);
$router->get('panel', [$home, 'panel']);
$router->get('usuarios-admin', [$usuariosAdmin, 'index']);
$router->get('reporte-usuarios', [$usuariosAdmin, 'report']);
$router->post('estado-usuario', [$usuariosAdmin, 'updateStatus']);
$router->get('categorias', static function () use ($categorias): void {
    AuthMiddleware::requireRole(['administrador']);
    $categorias->index();
});
$router->get('perfil', [$profile, 'show']);
$ventas = new VentasController(new Venta($database->connection()), new Categoria($database->connection()));
$router->get('ventas-instructor', [$ventas, 'index']);
$router->get('mis-cursos', static function () use ($cursos): void {
    AuthMiddleware::requireRole(['instructor']);
    $cursos->index();
});
$router->post('perfil', [$profile, 'update']);
$router->get('niveles', static function () use ($niveles): void {
    AuthMiddleware::requireRole(['instructor']);
    $niveles->index();
});
$router->get('archivo-nivel', static function () use ($niveles): void {
    AuthMiddleware::requireRole(['instructor']);
    $niveles->file();
});
$router->get('pago', [$payment, 'show']);
$router->post('paypal-crear', [$payment, 'create']);
$router->get('paypal-capturar', [$payment, 'capture']);
$router->get('paypal-cancelar', [$payment, 'cancel']);
$router->post('logout', [$auth, 'logout']);

$route = trim((string) ($_GET['r'] ?? 'inicio'));
$router->dispatch($_SERVER['REQUEST_METHOD'], $route);


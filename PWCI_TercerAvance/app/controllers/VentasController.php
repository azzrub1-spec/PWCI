<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\CategoriaRepository;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Venta;
use DateTimeImmutable;

final class VentasController
{
    public function __construct(private Venta $ventas, private CategoriaRepository $categorias)
    {
    }

    public function index(): void
    {
        AuthMiddleware::requireRole(['instructor']);
        $instructor = (int) $_SESSION['usuario']['id'];
        $filters = [];
        $error = null;
        foreach (['desde', 'hasta', 'categoria', 'activos'] as $key) {
            $value = $_GET[$key] ?? '';
            if (!is_string($value)) {
                $error = 'Revisa los filtros ingresados.';
                $value = '';
            }
            $filters[$key] = $value;
        }
        foreach (['desde', 'hasta'] as $key) {
            if ($filters[$key] !== '' && !$this->validDate($filters[$key])) {
                $error = 'Las fechas deben ser válidas.';
                $filters[$key] = '';
            }
        }
        if ($filters['desde'] !== '' && $filters['hasta'] !== '' && $filters['desde'] > $filters['hasta']) {
            $error = 'La fecha inicial no puede ser posterior a la final.';
        }
        if (($filters['categoria'] !== '' && !$this->positiveId($filters['categoria']))
            || !in_array($filters['activos'], ['', '1'], true)) {
            $error = 'Revisa los filtros de categoría y estado.';
        }

        $courseId = $_GET['curso'] ?? null;
        if ($courseId !== null && !$this->positiveId($courseId)) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        $courses = $error === null ? $this->ventas->courses($instructor, $filters, $courseId === null ? null : (int) $courseId) : [];
        $course = $courseId !== null ? ($courses[0] ?? null) : null;
        if ($error === null && $courseId !== null && $course === null) {
            http_response_code(404);
            View::render('errors/404');
            return;
        }

        View::render('instructor/ventas', [
            'courses' => $courses,
            'course' => $course,
            'students' => $course !== null ? $this->ventas->students($instructor, (int) $courseId) : [],
            'payments' => $error === null && $courseId === null ? $this->ventas->byPaymentMethod($instructor, $filters) : [],
            'categories' => $this->categorias->all(),
            'filters' => $filters,
            'error' => $error,
        ]);
    }

    private function positiveId(mixed $value): bool
    {
        return is_string($value)
            && filter_var($value, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) !== false;
    }

    private function validDate(string $value): bool
    {
        if (!preg_match('/^[1-9][0-9]{3}-[0-9]{2}-[0-9]{2}$/D', $value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}

<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\CursoRepository;
use App\Contracts\NivelRepository;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Inscripcion;
use App\Services\ArchivoNivel;

final class EstudianteController
{
    public function __construct(
        private Inscripcion $inscripciones,
        private CursoRepository $cursos,
        private NivelRepository $niveles,
        private ArchivoNivel $archivos
    ) {
    }

    public function detail(): void
    {
        $id = $this->queryId('curso');
        $course = $id ? $this->cursos->findActive($id) : null;
        if ($course === null) {
            $this->notFound();
            return;
        }

        $student = ($_SESSION['usuario']['rol'] ?? '') === 'estudiante';
        $enrollment = $student ? $this->inscripciones->find($this->studentId(), $id) : null;
        $completed = $enrollment ? $this->inscripciones->completedLevels((int) $enrollment['id_inscripcion']) : [];
        View::render('estudiante/curso', [
            'curso' => $course,
            'niveles' => $this->niveles->byCourse($id),
            'inscrito' => $enrollment !== null,
            'completados' => $completed,
            'esEstudiante' => $student,
            'csrf' => $student ? AuthMiddleware::csrfToken() : '',
        ]);
    }

    public function index(): void
    {
        View::render('estudiante/inscripciones', ['cursos' => $this->inscripciones->byStudent($this->studentId())]);
    }

    public function lesson(): void
    {
        $level = $this->currentLevel();
        if ($level === null) {
            $this->notFound();
            return;
        }

        $this->inscripciones->recordAccess((int) $level['id_inscripcion']);
        View::render('estudiante/nivel', ['nivel' => $level, 'csrf' => AuthMiddleware::csrfToken()]);
    }

    public function file(): void
    {
        $level = $this->currentLevel();
        $type = $_GET['tipo'] ?? '';
        $columns = ['video' => 'video_url', 'pdf' => 'archivo_pdf', 'imagen' => 'imagen'];

        if ($level === null || !is_string($type) || !isset($columns[$type])
            || !$this->archivos->exists($level[$columns[$type]] ?? null, $type)) {
            $this->notFound();
            return;
        }

        $this->archivos->send($level[$columns[$type]], $type);
    }

    public function api(string $resource, string $method, ?int $id): never
    {
        $student = $this->studentId();
        if ($resource === 'inscripciones' && $method === 'GET' && $id === null) {
            $this->respond(200, [
                'success' => true,
                'cursos' => $this->inscripciones->byStudent($student),
                'csrf_token' => AuthMiddleware::csrfToken(),
            ]);
        }

        if ($method !== 'POST' || $id === null) {
            $this->respond(404, ['success' => false, 'message' => 'Ruta o método no disponible.']);
        }

        if ($resource === 'inscripciones') {
            if ($this->cursos->findActive($id) === null) {
                $this->respond(404, ['success' => false, 'message' => 'Curso no encontrado.']);
            }
            if (!$this->inscripciones->enroll($student, $id)) {
                $this->respond(409, ['success' => false, 'message' => 'Este curso no tiene niveles gratuitos disponibles.']);
            }
            $this->respond(200, ['success' => true, 'message' => 'Inscripción registrada.']);
        }

        if (!$this->inscripciones->complete($student, $id)) {
            $this->respond(404, ['success' => false, 'message' => 'Nivel no disponible para esta cuenta.']);
        }
        $this->respond(200, ['success' => true, 'message' => 'Nivel marcado como completado.']);
    }

    private function currentLevel(): ?array
    {
        $id = $this->queryId('id');

        return $id ? $this->inscripciones->accessibleLevel($this->studentId(), $id) : null;
    }

    private function studentId(): int
    {
        return (int) $_SESSION['usuario']['id'];
    }

    private function queryId(string $key): ?int
    {
        return filter_var($_GET[$key] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    private function notFound(): void
    {
        http_response_code(404);
        View::render('errors/404');
    }

    private function respond(int $status, array $body): never
    {
        http_response_code($status);
        echo json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

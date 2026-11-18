<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Contracts\CursoRepository;
use App\Contracts\NivelRepository;
use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Inscripcion;
use App\Models\Resena;
use App\Services\ArchivoNivel;
use DateTimeImmutable;

final class EstudianteController
{
    public function __construct(
        private Inscripcion $inscripciones,
        private CursoRepository $cursos,
        private NivelRepository $niveles,
        private ArchivoNivel $archivos,
        private Resena $resenas
    ) {
    }

    public function detail(): void
    {
        AuthMiddleware::requireSession();
        $id = $this->queryId('curso');
        $course = $id ? $this->cursos->findActive($id) : null;
        if ($course === null) {
            $this->notFound();
            return;
        }

        $student = ($_SESSION['usuario']['rol'] ?? '') === 'estudiante';
        $admin = ($_SESSION['usuario']['rol'] ?? '') === 'administrador';
        $notice = $_SESSION['aviso_resena'] ?? null;
        if (($notice['curso'] ?? null) === $id) {
            unset($_SESSION['aviso_resena']);
        } else {
            $notice = null;
        }
        $enrollment = $student ? $this->inscripciones->find($this->studentId(), $id) : null;
        $completed = $enrollment ? $this->inscripciones->completedLevels((int) $enrollment['id_inscripcion']) : [];
        $purchases = $enrollment
            ? $this->inscripciones->paidLevels((int) $enrollment['id_inscripcion'])
            : [];
        $levels = $this->niveles->byCourse($id);
        $free = array_filter($levels, static fn (array $level): bool =>
            (bool) $level['gratis'] && (float) $level['costo_nivel'] === 0.0
        );
        $allowed = $admin
            || (($_SESSION['usuario']['rol'] ?? '') === 'instructor'
                && (int) $course['id_instructor'] === (int) $_SESSION['usuario']['id'])
            || ($enrollment !== null && ($purchases !== [] || $free !== []));

        View::render('estudiante/curso', [
            'puedeVerDetalle' => $allowed,
            'curso' => $course,
            'niveles' => $levels,
            'inscrito' => $enrollment !== null,
            'compras' => $purchases,
            'completados' => $completed,
            'esEstudiante' => $student,
            'csrf' => ($student || $admin) ? AuthMiddleware::csrfToken() : '',
            'esAdministrador' => $admin,
            'puedeOpinar' => $student && $this->resenas->canReview($id, $this->studentId()),
            'calificacion' => $this->resenas->rating($id),
            'comentarios' => $this->resenas->comments($id),
            'avisoResena' => $notice,
        ]);
    }

    public function index(): void
    {
        $this->inscripciones->issueCertificates($this->studentId());
        $cursos = $this->inscripciones->byStudent($this->studentId());
        $categorias = [];
        foreach ($cursos as $curso) {
            $categorias[(int) $curso['id_categoria']] = $curso['categoria'];
        }
        asort($categorias, SORT_NATURAL | SORT_FLAG_CASE);

        $filtros = [];
        $errores = [];
        foreach (['desde', 'hasta', 'categoria', 'terminados', 'activos'] as $campo) {
            $valor = $_GET[$campo] ?? '';
            if (!is_string($valor)) {
                $errores[] = 'El filtro ' . $campo . ' no es válido.';
                $valor = '';
            }
            $filtros[$campo] = trim($valor);
        }

        foreach (['desde', 'hasta'] as $campo) {
            $valor = $filtros[$campo];
            if ($valor !== '' && !$this->validKardexDate($valor)) {
                $errores[] = 'La fecha «' . ucfirst($campo) . '» no es válida.';
                $filtros[$campo] = '';
            }
        }
        if ($filtros['desde'] !== '' && $filtros['hasta'] !== '' && $filtros['desde'] > $filtros['hasta']) {
            $errores[] = 'La fecha «Desde» no puede ser posterior a «Hasta».';
        }

        $categoria = $filtros['categoria'];
        if ($categoria !== '' && (!ctype_digit($categoria) || !isset($categorias[(int) $categoria]))) {
            $errores[] = 'Selecciona una categoría de tus cursos.';
            $filtros['categoria'] = '';
        }

        $resultados = [];
        if ($errores === []) {
            foreach ($cursos as $curso) {
                $dia = substr($curso['fecha_inscripcion'], 0, 10);
                $curso['terminado'] = (int) $curso['total'] > 0
                    && (int) $curso['completados'] === (int) $curso['total'];
                if (($filtros['desde'] !== '' && $dia < $filtros['desde'])
                    || ($filtros['hasta'] !== '' && $dia > $filtros['hasta'])
                    || ($filtros['categoria'] !== '' && (int) $curso['id_categoria'] !== (int) $filtros['categoria'])
                    || ($filtros['terminados'] === '1' && !$curso['terminado'])
                    || ($filtros['activos'] === '1' && !(bool) $curso['activo'])) {
                    continue;
                }
                $resultados[] = $curso;
            }
        }

        View::render('estudiante/inscripciones', [
            'cursos' => $resultados,
            'totalInscritos' => count($cursos),
            'categorias' => $categorias,
            'filtros' => $filtros,
            'errores' => $errores,
        ]);
    }

    private function validKardexDate(string $value): bool
    {
        if (!preg_match('/\A\d{4}-\d{2}-\d{2}\z/', $value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date !== false && $date->format('Y-m-d') === $value && substr($value, 0, 4) !== '0000';
    }

    public function certificate(): void
    {
        $course = $this->queryId('curso');
        $certificate = $course ? $this->inscripciones->certificate($this->studentId(), $course) : null;
        if ($certificate === null) {
            $this->notFound();
            return;
        }

        View::render('estudiante/certificado', ['certificado' => $certificate]);
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

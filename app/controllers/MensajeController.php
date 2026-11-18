<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Mensaje;
use PDOException;

final class MensajeController
{
    public function __construct(private Mensaje $mensajes)
    {
    }

    public function index(): void
    {
        AuthMiddleware::requireRole(['estudiante', 'instructor']);
        $isStudent = $_SESSION['usuario']['rol'] === 'estudiante';
        $user = (int) $_SESSION['usuario']['id'];
        View::render('mensajes/index', [
            'conversaciones' => $this->mensajes->conversations($user, $isStudent),
            'esEstudiante' => $isStudent,
        ]);
    }

    public function show(): void
    {
        $thread = $this->findThread($_GET);
        if ($thread === null) {
            return;
        }

        $url = $this->url($thread);
        $notice = $_SESSION['aviso_mensaje'] ?? null;
        if (($notice['url'] ?? null) === $url) {
            unset($_SESSION['aviso_mensaje']);
        } else {
            $notice = null;
        }

        View::render('mensajes/conversacion', [
            'conversacion' => $thread,
            'historial' => $this->mensajes->history(
                (int) $thread['id_curso'],
                (int) $thread['id_estudiante'],
                (int) $thread['id_instructor']
            ),
            'esEstudiante' => $_SESSION['usuario']['rol'] === 'estudiante',
            'puedeEnviar' => $this->canSend($thread),
            'csrf' => AuthMiddleware::csrfToken(),
            'aviso' => $notice,
        ]);
    }

    public function send(): void
    {
        AuthMiddleware::requireRole(['estudiante', 'instructor']);
        $token = $_POST['csrf'] ?? null;
        if (!is_string($token) || !hash_equals(AuthMiddleware::csrfToken(), $token)) {
            http_response_code(403);
            View::render('errors/403');
            return;
        }

        $thread = $this->findThread($_POST);
        if ($thread === null) {
            return;
        }

        $message = $_POST['texto'] ?? null;
        $message = is_string($message) ? trim($message) : '';
        if (!$this->canSend($thread)) {
            $this->back($thread, 'Esta conversación ya no admite mensajes.', false);
        }
        if (preg_match('/\A.{1,1000}\z/us', $message) !== 1) {
            $this->back($thread, 'Escribe un mensaje de entre 1 y 1000 caracteres.', false);
        }

        $recipient = $_SESSION['usuario']['rol'] === 'estudiante'
            ? $thread['id_instructor'] : $thread['id_estudiante'];
        try {
            $this->mensajes->send(
                (int) $thread['id_curso'],
                (int) $_SESSION['usuario']['id'],
                (int) $recipient,
                $message
            );
        } catch (PDOException) {
            $this->back($thread, 'No se pudo enviar el mensaje. Intenta de nuevo.', false);
        }
        $this->back($thread, 'Mensaje enviado.', true);
    }

    private function findThread(array $input): ?array
    {
        AuthMiddleware::requireRole(['estudiante', 'instructor']);
        $isStudent = $_SESSION['usuario']['rol'] === 'estudiante';
        $user = (int) $_SESSION['usuario']['id'];
        $options = ['options' => ['min_range' => 1]];
        $course = filter_var($input['curso'] ?? null, FILTER_VALIDATE_INT, $options);
        $student = $isStudent ? $user : filter_var($input['alumno'] ?? null, FILTER_VALIDATE_INT, $options);
        $thread = $course && $student ? $this->mensajes->thread($course, $user, $isStudent, $student) : null;

        if ($thread === null) {
            http_response_code(404);
            View::render('errors/404');
        }
        return $thread;
    }

    private function canSend(array $thread): bool
    {
        return $thread['curso_activo'] && $thread['estudiante_activo'] && $thread['instructor_activo'];
    }

    private function url(array $thread): string
    {
        $url = BASE_URL . '/index.php?r=conversacion&curso=' . (int) $thread['id_curso'];
        if ($_SESSION['usuario']['rol'] === 'instructor') {
            $url .= '&alumno=' . (int) $thread['id_estudiante'];
        }
        return $url;
    }

    private function back(array $thread, string $message, bool $success): never
    {
        $url = $this->url($thread);
        $_SESSION['aviso_mensaje'] = ['url' => $url, 'mensaje' => $message, 'exito' => $success];
        header('Location: ' . $url, true, 303);
        exit;
    }
}

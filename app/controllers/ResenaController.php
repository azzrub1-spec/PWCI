<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\View;
use App\Middleware\AuthMiddleware;
use App\Models\Resena;

final class ResenaController
{
    public function __construct(private Resena $resenas)
    {
    }

    public function publish(): void
    {
        AuthMiddleware::requireRole(['estudiante']);
        $course = $this->validatedCourse();
        if ($course === null) {
            return;
        }
        $student = (int) $_SESSION['usuario']['id'];
        $score = filter_var($_POST['puntuacion'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 5]]);
        $comment = $_POST['texto'] ?? '';
        if ($score === false || !is_string($comment) || !preg_match('/\A.{0,1000}\z/us', trim($comment))) {
            $this->back($course, 'Elige de 1 a 5 estrellas y escribe como máximo 1000 caracteres.', false);
        }
        if (!$this->resenas->canReview($course, $student)) {
            $this->back($course, 'Debes terminar todos los niveles del curso antes de opinar.', false);
        }
        $this->resenas->save($course, $student, $score, trim($comment));
        $this->back($course, 'Tu opinión se guardó.', true);
    }

    public function moderate(): void
    {
        AuthMiddleware::requireRole(['administrador']);
        $course = $this->validatedCourse();
        if ($course === null) {
            return;
        }
        $comment = filter_var($_POST['comentario'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        $reason = $_POST['causa'] ?? null;
        if (!$comment || !is_string($reason) || !preg_match('/\A.{1,255}\z/us', trim($reason))) {
            $this->back($course, 'Indica una causa de entre 1 y 255 caracteres.', false);
        }
        if (!$this->resenas->remove($course, $comment, trim($reason))) {
            $this->back($course, 'El comentario no existe o ya fue eliminado.', false);
        }
        $this->back($course, 'Comentario eliminado.', true);
    }

    private function validatedCourse(): ?int
    {
        $token = $_POST['csrf'] ?? null;
        if (!is_string($token) || !hash_equals(AuthMiddleware::csrfToken(), $token)) {
            http_response_code(403);
            View::render('errors/403');
            return null;
        }
        $course = filter_var($_POST['curso'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (!$course) {
            http_response_code(404);
            View::render('errors/404');
            return null;
        }
        return $course;
    }

    private function back(int $course, string $message, bool $success): never
    {
        $_SESSION['aviso_resena'] = ['curso' => $course, 'mensaje' => $message, 'exito' => $success];
        header('Location: ' . BASE_URL . '/index.php?r=curso&curso=' . $course . '#opiniones', true, 303);
        exit;
    }
}

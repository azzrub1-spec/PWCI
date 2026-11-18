<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

final class Resena
{
    public function __construct(private PDO $database)
    {
    }

    public function comments(int $course): array
    {
        $query = $this->database->prepare(
            'SELECT cm.id_comentario, cm.fecha_creacion, cm.eliminado, cm.fecha_eliminacion,
                    cm.causa_eliminacion, u.nombre, u.apellidos,
                    CASE WHEN cm.eliminado = 0 THEN cm.texto ELSE NULL END AS texto
             FROM comentario cm JOIN usuario u ON u.id_usuario = cm.id_usuario
             WHERE cm.id_curso = :curso
             ORDER BY cm.fecha_creacion DESC, cm.id_comentario DESC'
        );
        $query->execute(['curso' => $course]);
        return $query->fetchAll();
    }

    public function rating(int $course): array
    {
        $query = $this->database->prepare(
            'SELECT COUNT(*) AS cantidad, AVG(puntuacion) AS promedio FROM calificacion WHERE id_curso = :curso'
        );
        $query->execute(['curso' => $course]);
        return $query->fetch();
    }

    public function canReview(int $course, int $student): bool
    {
        $query = $this->database->prepare(
            'SELECT 1 FROM inscripcion i JOIN curso c ON c.id_curso = i.id_curso
             WHERE i.id_estudiante = :estudiante AND i.id_curso = :curso AND c.activo = 1
               AND EXISTS (SELECT 1 FROM nivel n WHERE n.id_curso = i.id_curso)
               AND NOT EXISTS (
                   SELECT 1 FROM nivel n WHERE n.id_curso = i.id_curso
                     AND NOT EXISTS (
                         SELECT 1 FROM progreso_nivel p WHERE p.id_inscripcion = i.id_inscripcion
                           AND p.id_nivel = n.id_nivel AND p.completado = 1
                     )
               )'
        );
        $query->execute(['estudiante' => $student, 'curso' => $course]);
        return $query->fetchColumn() !== false;
    }

    public function save(int $course, int $student, int $score, string $comment): void
    {
        $this->database->beginTransaction();
        try {
            $query = $this->database->prepare(
                'INSERT INTO calificacion (id_usuario, id_curso, puntuacion)
                 VALUES (:usuario, :curso, :puntuacion)
                 ON DUPLICATE KEY UPDATE puntuacion = :nueva'
            );
            $query->execute(['usuario' => $student, 'curso' => $course, 'puntuacion' => $score, 'nueva' => $score]);
            if ($comment !== '') {
                $query = $this->database->prepare(
                    'INSERT INTO comentario (id_usuario, id_curso, texto) VALUES (:usuario, :curso, :texto)'
                );
                $query->execute(['usuario' => $student, 'curso' => $course, 'texto' => $comment]);
            }
            $this->database->commit();
        } catch (Throwable $error) {
            $this->database->rollBack();
            throw $error;
        }
    }

    public function remove(int $course, int $comment, string $reason): bool
    {
        $query = $this->database->prepare(
            'UPDATE comentario
             SET eliminado = 1, fecha_eliminacion = CURRENT_TIMESTAMP, causa_eliminacion = :causa
             WHERE id_comentario = :comentario AND id_curso = :curso AND eliminado = 0'
        );
        $query->execute(['curso' => $course, 'comentario' => $comment, 'causa' => $reason]);
        return $query->rowCount() === 1;
    }
}

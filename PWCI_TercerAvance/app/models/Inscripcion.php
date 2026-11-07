<?php

declare(strict_types=1);

namespace App\Models;

use PDO;
use Throwable;

final class Inscripcion
{
    public function __construct(private PDO $database)
    {
    }

    public function byStudent(int $student): array
    {
        $query = $this->database->prepare(
            'SELECT i.id_curso, i.fecha_inscripcion, i.fecha_ultimo_acceso, i.fecha_terminacion,
                    c.titulo, c.activo, ca.nombre AS categoria,
                    (SELECT COUNT(*) FROM nivel n WHERE n.id_curso = i.id_curso) AS total,
                    (SELECT COUNT(*) FROM progreso_nivel p
                     JOIN nivel n ON n.id_nivel = p.id_nivel
                     WHERE p.id_inscripcion = i.id_inscripcion AND n.id_curso = i.id_curso
                       AND p.completado = 1) AS completados
             FROM inscripcion i JOIN curso c ON c.id_curso = i.id_curso
             JOIN categoria ca ON ca.id_categoria = c.id_categoria
             WHERE i.id_estudiante = :estudiante ORDER BY i.fecha_inscripcion DESC, i.id_inscripcion DESC'
        );
        $query->execute(['estudiante' => $student]);

        return $query->fetchAll();
    }

    public function find(int $student, int $course): ?array
    {
        $query = $this->database->prepare(
            'SELECT id_inscripcion FROM inscripcion WHERE id_estudiante = :estudiante AND id_curso = :curso'
        );
        $query->execute(['estudiante' => $student, 'curso' => $course]);

        return $query->fetch() ?: null;
    }

    public function enroll(int $student, int $course): bool
    {
        $query = $this->database->prepare(
            'INSERT INTO inscripcion (id_estudiante, id_curso)
             SELECT :estudiante, c.id_curso FROM curso c
             WHERE c.id_curso = :curso AND c.activo = 1
               AND EXISTS (SELECT 1 FROM nivel n WHERE n.id_curso = c.id_curso
                           AND n.gratis = 1 AND n.costo_nivel = 0)
             ON DUPLICATE KEY UPDATE id_inscripcion = inscripcion.id_inscripcion'
        );
        $query->execute(['estudiante' => $student, 'curso' => $course]);

        return $this->find($student, $course) !== null;
    }

    public function completedLevels(int $enrollment): array
    {
        $query = $this->database->prepare(
            'SELECT id_nivel FROM progreso_nivel WHERE id_inscripcion = :inscripcion AND completado = 1'
        );
        $query->execute(['inscripcion' => $enrollment]);

        return array_map('intval', $query->fetchAll(PDO::FETCH_COLUMN));
    }

    public function accessibleLevel(int $student, int $level): ?array
    {
        return $this->findAccessibleLevel($student, $level, false);
    }

    private function findAccessibleLevel(int $student, int $level, bool $lock): ?array
    {
        // La inscripción por sí sola nunca da acceso al contenido de pago.
        $sql = 'SELECT n.*, i.id_inscripcion, c.titulo AS curso_titulo,
                       COALESCE(p.completado, 0) AS completado
                FROM nivel n JOIN curso c ON c.id_curso = n.id_curso
                JOIN inscripcion i ON i.id_curso = c.id_curso
                LEFT JOIN progreso_nivel p ON p.id_inscripcion = i.id_inscripcion AND p.id_nivel = n.id_nivel
                WHERE i.id_estudiante = :estudiante AND n.id_nivel = :nivel
                  AND c.activo = 1 AND n.gratis = 1 AND n.costo_nivel = 0';
        $query = $this->database->prepare($sql . ($lock ? ' FOR UPDATE' : ''));
        $query->execute(['estudiante' => $student, 'nivel' => $level]);

        return $query->fetch() ?: null;
    }

    public function recordAccess(int $enrollment): void
    {
        $query = $this->database->prepare(
            'UPDATE inscripcion SET fecha_ultimo_acceso = NOW() WHERE id_inscripcion = :id'
        );
        $query->execute(['id' => $enrollment]);
    }

    public function complete(int $student, int $level): bool
    {
        $this->database->beginTransaction();
        try {
            $access = $this->findAccessibleLevel($student, $level, true);
            if ($access === null) {
                $this->database->rollBack();
                return false;
            }

            $enrollment = (int) $access['id_inscripcion'];
            $query = $this->database->prepare(
                'INSERT INTO progreso_nivel (id_inscripcion, id_nivel, completado, fecha_completado)
                 VALUES (:inscripcion, :nivel, 1, NOW())
                 ON DUPLICATE KEY UPDATE completado = 1, fecha_completado = COALESCE(fecha_completado, NOW())'
            );
            $query->execute(['inscripcion' => $enrollment, 'nivel' => $level]);

            $update = $this->database->prepare(
                'UPDATE inscripcion i
                 SET fecha_ultimo_acceso = NOW(), fecha_terminacion = CASE WHEN NOT EXISTS (
                     SELECT 1 FROM nivel n WHERE n.id_curso = i.id_curso AND NOT EXISTS (
                         SELECT 1 FROM progreso_nivel p WHERE p.id_inscripcion = i.id_inscripcion
                           AND p.id_nivel = n.id_nivel AND p.completado = 1
                     )
                 ) THEN COALESCE(i.fecha_terminacion, NOW()) ELSE NULL END
                 WHERE i.id_inscripcion = :inscripcion'
            );
            $update->execute(['inscripcion' => $enrollment]);
            $this->database->commit();

            return true;
        } catch (Throwable $exception) {
            if ($this->database->inTransaction()) {
                $this->database->rollBack();
            }
            throw $exception;
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Venta
{
    public function __construct(private PDO $database)
    {
    }

    public function courses(int $instructor, array $filters, ?int $course = null): array
    {
        [$where, $parameters] = $this->scope($instructor, $filters, $course);
        return $this->rows(
            "SELECT c.id_curso, c.titulo, c.activo, c.fecha_publicacion, ca.nombre AS categoria,
                    (SELECT COUNT(*) FROM inscripcion i WHERE i.id_curso = c.id_curso) AS inscritos,
                    (SELECT COUNT(*) FROM nivel n WHERE n.id_curso = c.id_curso) AS niveles,
                    (SELECT COUNT(*) FROM inscripcion i
                     JOIN progreso_nivel pr ON pr.id_inscripcion = i.id_inscripcion AND pr.completado = 1
                     JOIN nivel n ON n.id_nivel = pr.id_nivel AND n.id_curso = i.id_curso
                     WHERE i.id_curso = c.id_curso) AS niveles_cursados,
                    COALESCE((SELECT SUM(p.monto) FROM inscripcion i
                              JOIN pago p ON p.id_inscripcion = i.id_inscripcion
                              WHERE i.id_curso = c.id_curso), 0) AS ingresos
             FROM curso c JOIN categoria ca ON ca.id_categoria = c.id_categoria
             WHERE $where ORDER BY c.fecha_publicacion DESC, c.id_curso DESC",
            $parameters
        );
    }

    public function byPaymentMethod(int $instructor, array $filters): array
    {
        [$where, $parameters] = $this->scope($instructor, $filters);
        return $this->rows(
            "SELECT p.forma_pago, SUM(p.monto) AS ingresos
             FROM curso c JOIN inscripcion i ON i.id_curso = c.id_curso
             JOIN pago p ON p.id_inscripcion = i.id_inscripcion
             WHERE $where GROUP BY p.forma_pago ORDER BY p.forma_pago",
            $parameters
        );
    }

    public function students(int $instructor, int $course): array
    {
        return $this->rows(
            'SELECT u.nombre, u.apellidos, i.fecha_inscripcion,
                    (SELECT COUNT(*) FROM progreso_nivel pr
                     JOIN nivel n ON n.id_nivel = pr.id_nivel AND n.id_curso = i.id_curso
                     WHERE pr.id_inscripcion = i.id_inscripcion AND pr.completado = 1) AS cursados,
                    COALESCE((SELECT SUM(p.monto) FROM pago p
                              WHERE p.id_inscripcion = i.id_inscripcion), 0) AS pagado,
                    (SELECT GROUP_CONCAT(DISTINCT p.forma_pago) FROM pago p
                     WHERE p.id_inscripcion = i.id_inscripcion) AS formas
             FROM inscripcion i JOIN usuario u ON u.id_usuario = i.id_estudiante
             JOIN curso c ON c.id_curso = i.id_curso
             WHERE c.id_instructor = :instructor AND c.id_curso = :curso
             ORDER BY u.apellidos, u.nombre, i.id_inscripcion',
            ['instructor' => $instructor, 'curso' => $course]
        );
    }

    private function scope(int $instructor, array $filters, ?int $course = null): array
    {
        $conditions = ['c.id_instructor = :instructor'];
        $parameters = ['instructor' => $instructor];
        if ($course !== null) {
            $conditions[] = 'c.id_curso = :curso';
            $parameters['curso'] = $course;
        }
        if ($filters['desde'] !== '') {
            $conditions[] = 'c.fecha_publicacion >= :desde';
            $parameters['desde'] = $filters['desde'] . ' 00:00:00';
        }
        if ($filters['hasta'] !== '') {
            $conditions[] = 'c.fecha_publicacion <= :hasta';
            $parameters['hasta'] = $filters['hasta'] . ' 23:59:59';
        }
        if ($filters['categoria'] !== '') {
            $conditions[] = 'c.id_categoria = :categoria';
            $parameters['categoria'] = $filters['categoria'];
        }
        if ($filters['activos'] === '1') {
            $conditions[] = 'c.activo = 1';
        }
        return [implode(' AND ', $conditions), $parameters];
    }

    private function rows(string $sql, array $parameters): array
    {
        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);
        return $statement->fetchAll();
    }
}

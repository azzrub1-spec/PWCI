<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\NivelRepository;
use PDO;

final class Nivel implements NivelRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function byCourse(int $courseId): array
    {
        $statement = $this->database->prepare(
            'SELECT * FROM nivel WHERE id_curso = :curso ORDER BY orden, id_nivel'
        );
        $statement->execute(['curso' => $courseId]);

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT * FROM nivel WHERE id_nivel = :id'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO nivel
                (id_curso, orden, titulo, video_url, contenido_texto,
                 costo_nivel, gratis, archivo_pdf, imagen, enlace)
             VALUES
                (:id_curso, :orden, :titulo, :video_url, :contenido_texto,
                 :costo_nivel, :gratis, :archivo_pdf, :imagen, :enlace)'
        );
        $statement->execute($data);

        return (int) $this->database->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $statement = $this->database->prepare(
            'UPDATE nivel
             SET orden = :orden, titulo = :titulo, video_url = :video_url,
                 contenido_texto = :contenido_texto, costo_nivel = :costo_nivel,
                 gratis = :gratis, archivo_pdf = :archivo_pdf,
                 imagen = :imagen, enlace = :enlace
             WHERE id_nivel = :id AND id_curso = :id_curso'
        );
        $statement->execute($data);
    }

    public function delete(int $id): bool
    {
        // No borrar niveles que puedan formar parte del avance de estudiantes.
        $statement = $this->database->prepare(
            'DELETE FROM nivel
             WHERE id_nivel = :id
               AND NOT EXISTS (
                   SELECT 1 FROM inscripcion i WHERE i.id_curso = nivel.id_curso
               )'
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }
}

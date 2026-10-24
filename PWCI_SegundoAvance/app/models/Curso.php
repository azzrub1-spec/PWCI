<?php

declare(strict_types=1);

namespace App\Models;

use PDO;

final class Curso
{
    public function __construct(private PDO $database)
    {
    }

    public function active(array $filters = []): array
    {
        $sql = 'SELECT c.id_curso, c.titulo, c.descripcion, c.imagen, c.costo,
                       c.fecha_publicacion, ca.nombre AS categoria,
                       CONCAT(u.nombre, " ", u.apellidos) AS instructor
                FROM curso c
                INNER JOIN categoria ca ON ca.id_categoria = c.id_categoria
                INNER JOIN usuario u ON u.id_usuario = c.id_instructor
                WHERE c.activo = 1';
        $parameters = [];

        if (!empty($filters['titulo'])) {
            $sql .= ' AND c.titulo LIKE :titulo';
            $parameters['titulo'] = '%' . $filters['titulo'] . '%';
        }

        if (!empty($filters['categoria'])) {
            $sql .= ' AND c.id_categoria = :categoria';
            $parameters['categoria'] = (int) $filters['categoria'];
        }

        $sql .= ' ORDER BY c.fecha_publicacion DESC';
        $statement = $this->database->prepare($sql);
        $statement->execute($parameters);

        return $statement->fetchAll();
    }

    public function findActive(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT c.*, ca.nombre AS categoria,
                    CONCAT(u.nombre, " ", u.apellidos) AS instructor
             FROM curso c
             INNER JOIN categoria ca ON ca.id_categoria = c.id_categoria
             INNER JOIN usuario u ON u.id_usuario = c.id_instructor
             WHERE c.id_curso = :id AND c.activo = 1'
        );
        $statement->execute(['id' => $id]);
        $curso = $statement->fetch();

        return $curso ?: null;
    }

    public function create(array $data): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO curso
                (id_instructor, id_categoria, titulo, descripcion, imagen,
                 costo, activo, fecha_publicacion)
             VALUES
                (:id_instructor, :id_categoria, :titulo, :descripcion,
                 :imagen, :costo, 1, NOW())'
        );
        $statement->execute($data);

        return (int) $this->database->lastInsertId();
    }

    public function softDelete(int $id, int $instructorId): bool
    {
        $statement = $this->database->prepare(
            'UPDATE curso SET activo = 0
             WHERE id_curso = :id AND id_instructor = :instructor'
        );
        $statement->execute([
            'id' => $id,
            'instructor' => $instructorId,
        ]);

        return $statement->rowCount() === 1;
    }
}


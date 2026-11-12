<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\CursoRepository;
use PDO;

final class Curso implements CursoRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function active(array $filters = []): array
    {
        $sql = 'SELECT c.id_curso, c.titulo, c.imagen, c.costo,
                       c.fecha_publicacion, ca.nombre AS categoria,
                       CONCAT(u.nombre, " ", u.apellidos) AS instructor
                FROM curso c
                INNER JOIN categoria ca ON ca.id_categoria = c.id_categoria
                INNER JOIN usuario u ON u.id_usuario = c.id_instructor
                WHERE c.activo = 1';
        $parameters = [];

        foreach (['titulo' => 'c.titulo', 'instructor' => 'CONCAT(u.nombre, " ", u.apellidos)'] as $key => $column) {
            if (($filters[$key] ?? '') !== '') {
                $sql .= " AND $column LIKE :$key ESCAPE '!'";
                $parameters[$key] = '%' . strtr($filters[$key], ['!' => '!!', '%' => '!%', '_' => '!_']) . '%';
            }
        }

        if (!empty($filters['categoria'])) {
            $sql .= ' AND c.id_categoria = :categoria';
            $parameters['categoria'] = (int) $filters['categoria'];
        }

        if (($filters['desde'] ?? '') !== '') {
            $sql .= ' AND c.fecha_publicacion >= :desde';
            $parameters['desde'] = $filters['desde'] . ' 00:00:00';
        }
        if (($filters['hasta'] ?? '') !== '') {
            $sql .= ' AND c.fecha_publicacion <= :hasta';
            $parameters['hasta'] = $filters['hasta'] . ' 23:59:59';
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

    public function byInstructor(int $instructorId): array
    {
        $statement = $this->database->prepare(
            'SELECT c.*, ca.nombre AS categoria,
                    (SELECT COUNT(*) FROM nivel n WHERE n.id_curso = c.id_curso) AS total_niveles
             FROM curso c
             INNER JOIN categoria ca ON ca.id_categoria = c.id_categoria
             WHERE c.id_instructor = :instructor
             ORDER BY c.fecha_publicacion DESC, c.id_curso DESC'
        );
        $statement->execute(['instructor' => $instructorId]);

        return $statement->fetchAll();
    }

    public function findOwned(int $id, int $instructorId): ?array
    {
        $statement = $this->database->prepare(
            'SELECT * FROM curso
             WHERE id_curso = :id AND id_instructor = :instructor'
        );
        $statement->execute(['id' => $id, 'instructor' => $instructorId]);

        return $statement->fetch() ?: null;
    }

    public function update(int $id, int $instructorId, array $data): void
    {
        $statement = $this->database->prepare(
            'UPDATE curso
             SET titulo = :titulo, descripcion = :descripcion,
                 id_categoria = :id_categoria, costo = :costo, imagen = :imagen
             WHERE id_curso = :id AND id_instructor = :instructor AND activo = 1'
        );
        $statement->execute([
            'id' => $id,
            'instructor' => $instructorId,
            'titulo' => $data['titulo'],
            'descripcion' => $data['descripcion'],
            'id_categoria' => $data['id_categoria'],
            'costo' => $data['costo'],
            'imagen' => $data['imagen'],
        ]);
    }

    public function softDelete(int $id, int $instructorId): bool
    {
        $statement = $this->database->prepare(
            'UPDATE curso SET activo = 0
             WHERE id_curso = :id AND id_instructor = :instructor AND activo = 1'
        );
        $statement->execute([
            'id' => $id,
            'instructor' => $instructorId,
        ]);

        return $statement->rowCount() === 1;
    }
}


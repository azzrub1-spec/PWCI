<?php

declare(strict_types=1);

namespace App\Models;

use App\Contracts\CategoriaRepository;
use PDO;

final class Categoria implements CategoriaRepository
{
    public function __construct(private PDO $database)
    {
    }

    public function all(): array
    {
        $statement = $this->database->query(
            'SELECT c.id_categoria, c.nombre, c.descripcion, c.fecha_creacion,
                    c.id_usuario_creador,
                    CONCAT(u.nombre, " ", u.apellidos) AS creador
             FROM categoria c
             LEFT JOIN usuario u ON u.id_usuario = c.id_usuario_creador
             ORDER BY c.nombre'
        );

        return $statement->fetchAll();
    }

    public function find(int $id): ?array
    {
        $statement = $this->database->prepare(
            'SELECT * FROM categoria WHERE id_categoria = :id'
        );
        $statement->execute(['id' => $id]);

        return $statement->fetch() ?: null;
    }

    public function create(string $nombre, string $descripcion, int $creador): int
    {
        $statement = $this->database->prepare(
            'INSERT INTO categoria (nombre, descripcion, id_usuario_creador)
             VALUES (:nombre, :descripcion, :creador)'
        );
        $statement->execute([
            'nombre' => $nombre,
            'descripcion' => $descripcion,
            'creador' => $creador,
        ]);

        return (int) $this->database->lastInsertId();
    }

    public function update(int $id, string $nombre, string $descripcion): void
    {
        $statement = $this->database->prepare(
            'UPDATE categoria SET nombre = :nombre, descripcion = :descripcion
             WHERE id_categoria = :id'
        );
        $statement->execute([
            'id' => $id,
            'nombre' => $nombre,
            'descripcion' => $descripcion,
        ]);
    }

    public function delete(int $id): bool
    {
        // La llave foránea impide borrar categorías que todavía tienen cursos.
        $statement = $this->database->prepare(
            'DELETE FROM categoria WHERE id_categoria = :id'
        );
        $statement->execute(['id' => $id]);

        return $statement->rowCount() === 1;
    }
}

<?php

declare(strict_types=1);

namespace App\Contracts;

interface CategoriaRepository
{
    public function all(): array;

    public function find(int $id): ?array;

    public function create(string $nombre, string $descripcion, int $creador): int;

    public function update(int $id, string $nombre, string $descripcion): void;

    public function delete(int $id): bool;
}

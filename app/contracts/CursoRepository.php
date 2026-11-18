<?php

declare(strict_types=1);

namespace App\Contracts;

interface CursoRepository
{
    public function active(array $filters = []): array;

    public function findActive(int $id): ?array;

    public function byInstructor(int $instructorId): array;

    public function findOwned(int $id, int $instructorId): ?array;

    public function create(array $data): int;

    public function update(int $id, int $instructorId, array $data): void;

    public function softDelete(int $id, int $instructorId): bool;
}

<?php
declare(strict_types=1);

namespace App\Repositories;

interface ServiceRepositoryInterface
{
    public function all(): array;

    public function findById(int $id): ?array;

    public function create(string $name, int $durationMinutes, float $price): int;

    public function update(int $id, string $name, int $durationMinutes, float $price): bool;

    public function delete(int $id): bool;
}
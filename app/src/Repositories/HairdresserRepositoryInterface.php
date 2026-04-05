<?php
declare(strict_types=1);

namespace App\Repositories;

interface HairdresserRepositoryInterface
{
    public function all(): array;

    public function findById(int $id): ?array;

    public function create(string $name): int;

    public function update(int $id, string $name): bool;

    public function delete(int $id): bool;

    public function getWeeklyAvailability(int $hairdresserId): array;
}
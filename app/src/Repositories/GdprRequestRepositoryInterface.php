<?php
declare(strict_types=1);

namespace App\Repositories;

interface GdprRequestRepositoryInterface
{
    public function create(int $userId, string $type): int;

    public function allWithUsers(): array;

    public function markProcessed(int $id): bool;
}
<?php
declare(strict_types=1);

namespace App\Repositories;

interface UserRepositoryInterface
{
    public function allClients(): array;

    public function allClientsWithBookingStats(): array;

    public function findByEmail(string $email): ?array;

    public function findById(int $id): ?array;

    public function findAuthById(int $id): ?array;

    public function create(string $name, string $email, string $passwordHash, string $role = 'client'): int;

    public function updateName(int $id, string $name): bool;

    public function updateEmail(int $id, string $email): bool;

    public function updatePassword(int $id, string $passwordHash): bool;

    public function allStaff(): array;

    public function createStaff(string $name, string $email, string $passwordHash, int $hairdresserId): int;

    public function updateStaffHairdresser(int $staffId, int $hairdresserId): bool;

    public function deleteStaff(int $staffId): bool;
}
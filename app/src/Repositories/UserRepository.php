<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class UserRepository implements UserRepositoryInterface
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    private function findOneBy(string $whereSql, array $params, bool $withPasswordHash = false): ?array
    {
        $base = $withPasswordHash
            ? 'SELECT id, name, email, password_hash, role, hairdresser_id FROM users'
            : 'SELECT id, name, email, role, hairdresser_id FROM users';

        $stmt = $this->pdo->prepare($base . ' WHERE ' . $whereSql . ' LIMIT 1');
        $stmt->execute($params);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function allClients(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, email, role FROM users WHERE role = 'client' ORDER BY email ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function allClientsWithBookingStats(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT
                u.id,
                u.name,
                u.email,
                u.created_at,
                COUNT(a.id) AS total_appointments,
                SUM(CASE WHEN a.status = 'booked' THEN 1 ELSE 0 END) AS booked_count,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
                SUM(CASE WHEN a.status = 'cancelled' THEN 1 ELSE 0 END) AS cancelled_count
             FROM users u
             LEFT JOIN appointments a ON a.user_id = u.id
             WHERE u.role = 'client'
               GROUP BY u.id, u.name, u.email, u.created_at
             ORDER BY u.email ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findByEmail(string $email): ?array
    {
        return $this->findOneBy('email = :email', ['email' => $email], true);
    }

    public function findById(int $id): ?array
    {
        return $this->findOneBy('id = :id', ['id' => $id], false);
    }

    public function findAuthById(int $id): ?array
    {
        return $this->findOneBy('id = :id', ['id' => $id], true);
    }

    public function create(string $name, string $email, string $passwordHash, string $role = 'client'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, role_id)
             VALUES (:name, :email, :hash, :role, (SELECT id FROM roles WHERE name = :role LIMIT 1))'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'hash'  => $passwordHash,
            'role'  => $role,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    public function updateName(int $id, string $name): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET name = :name WHERE id = :id');
        $stmt->execute(['name' => $name, 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function updateEmail(int $id, string $email): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET email = :email WHERE id = :id');
        $stmt->execute(['email' => $email, 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    public function updatePassword(int $id, string $hash): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /**
     * Get all staff users with their hairdresser info
     * @return array<int, array<string, mixed>>
     */
    public function allStaff(): array
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.name, u.email, u.created_at, u.hairdresser_id, h.name AS hairdresser_name
             FROM users u
             LEFT JOIN hairdressers h ON h.id = u.hairdresser_id
             WHERE u.role = 'staff'
             ORDER BY u.name ASC"
        );
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Create a staff user linked to a hairdresser
     */
    public function createStaff(string $name, string $email, string $passwordHash, int $hairdresserId): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (name, email, password_hash, role, hairdresser_id, role_id)
             VALUES (:name, :email, :hash, :role, :hid, (SELECT id FROM roles WHERE name = :role LIMIT 1))'
        );
        $stmt->execute([
            'name' => $name,
            'email' => $email,
            'hash'  => $passwordHash,
            'role'  => 'staff',
            'hid'   => $hairdresserId,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * Update staff hairdresser assignment
     */
    public function updateStaffHairdresser(int $staffId, int $hairdresserId): bool
    {
        $stmt = $this->pdo->prepare('UPDATE users SET hairdresser_id = :hid WHERE id = :id AND role = :role');
        $stmt->execute(['hid' => $hairdresserId, 'id' => $staffId, 'role' => 'staff']);

        return $stmt->rowCount() > 0;
    }

    /**
     * Delete staff user
     */
    public function deleteStaff(int $staffId): bool
    {
        $stmt = $this->pdo->prepare('DELETE FROM users WHERE id = :id AND role = :role');
        $stmt->execute(['id' => $staffId, 'role' => 'staff']);

        return $stmt->rowCount() > 0;
    }
}

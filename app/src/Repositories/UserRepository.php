<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class UserRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    public function findByEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, role FROM users WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function findAuthById(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT id, email, password_hash, role FROM users WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function create(string $email, string $passwordHash, string $role = 'client'): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO users (email, password_hash, role) VALUES (:email, :hash, :role)'
        );
        $stmt->execute([
            'email' => $email,
            'hash'  => $passwordHash,
            'role'  => $role,
        ]);

        return (int)$this->pdo->lastInsertId();
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
}

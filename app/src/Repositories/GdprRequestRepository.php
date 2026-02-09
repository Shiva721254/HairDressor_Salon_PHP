<?php
declare(strict_types=1);

namespace App\Repositories;

use App\Core\Db;
use PDO;

final class GdprRequestRepository
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Db::pdo();
    }

    public function create(int $userId, string $type): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO gdpr_requests (user_id, type) VALUES (:uid, :type)'
        );
        $stmt->execute([
            'uid' => $userId,
            'type' => $type,
        ]);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function allWithUsers(): array
    {
        $sql = '
            SELECT
                r.id,
                r.user_id,
                r.type,
                r.status,
                r.created_at,
                u.email AS user_email,
                u.role AS user_role
            FROM gdpr_requests r
            JOIN users u ON u.id = r.user_id
            ORDER BY r.created_at DESC, r.id DESC
        ';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function markProcessed(int $id): bool
    {
        $stmt = $this->pdo->prepare(
            "UPDATE gdpr_requests SET status = 'processed' WHERE id = :id AND status = 'pending'"
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }
}

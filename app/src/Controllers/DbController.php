<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Db;
use PDOException;

final class DbController extends Controller
{
    public function health(): string
    {
        $status = 'OK';

        try {
            Db::pdo()->query('SELECT 1');
        } catch (PDOException $e) {
            $status = 'FAILED: ' . $e->getMessage();
        }

        return $this->render('db/health', [
            'title'  => 'DB Health',
            'status' => $status,
        ]);
    }
}

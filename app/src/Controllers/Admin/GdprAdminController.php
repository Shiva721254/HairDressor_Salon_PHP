<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\GdprRequestRepository;

final class GdprAdminController extends Controller
{
    public function index(): string
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $repo = new GdprRequestRepository();
        $requests = $repo->allWithUsers();

        return $this->render('admin/gdpr/index', [
            'title' => 'Admin - GDPR Requests',
            'requests' => $requests,
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function process(string $id): string
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $this->requireCsrf();

        $idInt = (int)$id;
        if ($idInt <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'GDPR request not found']);
        }

        $repo = new GdprRequestRepository();
        $ok = $repo->markProcessed($idInt);

        if ($ok) {
            $this->flash('success', 'GDPR request marked as processed.');
        } else {
            $this->flash('error', 'Unable to mark request as processed. It may already be processed.');
        }

        return $this->redirect('/admin/gdpr-requests');
    }
}

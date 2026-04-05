<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\GdprRequestRepositoryInterface;

final class GdprAdminController extends Controller
{
    public function __construct(private GdprRequestRepositoryInterface $gdprRequests)
    {
    }

    private function requireAdmin(): void
    {
        $this->requireRole('admin');
    }

    public function index(): string
    {
        $this->requireAdmin();

        $requests = $this->gdprRequests->allWithUsers();

        return $this->render('admin/gdpr/index', [
            'title' => 'Admin - GDPR Requests',
            'requests' => $requests,
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function process(string $id): string
    {
        $this->requireAdmin();

        $this->requireCsrf();

        $idInt = (int)$id;
        if ($idInt <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'GDPR request not found']);
        }

        $ok = $this->gdprRequests->markProcessed($idInt);

        if ($ok) {
            $this->flash('success', 'GDPR request marked as processed.');
        } else {
            $this->flash('error', 'Unable to mark request as processed. It may already be processed.');
        }

        return $this->redirect('/admin/gdpr-requests');
    }
}

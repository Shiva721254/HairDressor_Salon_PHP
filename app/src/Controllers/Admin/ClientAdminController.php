<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\AppointmentRepositoryInterface;
use App\Repositories\UserRepositoryInterface;

final class ClientAdminController extends Controller
{
    public function __construct(
        private UserRepositoryInterface $users,
        private AppointmentRepositoryInterface $appointments
    ) {
    }

    public function index(): string
    {
        $this->requireRole('admin');

        $clients = $this->users->allClientsWithBookingStats();
        foreach ($clients as &$client) {
            $clientId = (int)($client['id'] ?? 0);
            $client['booking_history'] = $clientId > 0
                ? $this->appointments->allWithDetails('all', $clientId)
                : [];
        }
        unset($client);

        return $this->render('admin/clients/index', [
            'title' => 'Admin - Clients',
            'clients' => $clients,
        ]);
    }
}

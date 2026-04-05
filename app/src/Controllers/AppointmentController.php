<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppointmentRepositoryInterface;
use App\Repositories\HairdresserRepositoryInterface;
use App\Repositories\ServiceRepositoryInterface;
use App\Repositories\UserRepositoryInterface;
use App\Services\AvailabilityService;
use App\Services\BookingService;

final class AppointmentController extends Controller
{
    public function __construct(
        private AppointmentRepositoryInterface $appointments,
        private HairdresserRepositoryInterface $hairdressers,
        private ServiceRepositoryInterface $services,
        private UserRepositoryInterface $users,
        private BookingService $bookingService,
        private AvailabilityService $availabilityService
    ) {
    }

    private function requireAdmin(): array
    {
        $user = $this->requireLogin();
        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            echo $this->render('errors/403', ['title' => 'Forbidden']);
            exit;
        }
        return $user;
    }

    public function index(): string
    {
        $user = $this->requireLogin();
        $isAdmin = (($user['role'] ?? '') === 'admin');

        $filter = $_GET['filter'] ?? 'upcoming';
        $filter = is_string($filter) ? strtolower($filter) : 'upcoming';

        $hairdresserId = null;
        $dateFrom = null;
        $dateTo = null;

        if ($isAdmin) {
            $hairdresserId = filter_input(INPUT_GET, 'hairdresser_id', FILTER_VALIDATE_INT) ?: null;

            $dateFromRaw = trim((string)($_GET['date_from'] ?? ''));
            $dateToRaw = trim((string)($_GET['date_to'] ?? ''));

            if ($dateFromRaw !== '') {
                $dtFrom = \DateTimeImmutable::createFromFormat('Y-m-d', $dateFromRaw);
                if ($dtFrom && $dtFrom->format('Y-m-d') === $dateFromRaw) {
                    $dateFrom = $dateFromRaw;
                }
            }

            if ($dateToRaw !== '') {
                $dtTo = \DateTimeImmutable::createFromFormat('Y-m-d', $dateToRaw);
                if ($dtTo && $dtTo->format('Y-m-d') === $dateToRaw) {
                    $dateTo = $dateToRaw;
                }
            }
        }

        $appointments = $this->appointments->allWithDetails(
            $filter,
            $isAdmin ? null : (int)($user['id'] ?? 0),
            $isAdmin ? $hairdresserId : null,
            $isAdmin ? $dateFrom : null,
            $isAdmin ? $dateTo : null
        );

        $hairdressers = [];
        if ($isAdmin) {
            $hairdressers = $this->hairdressers->all();
        }

        return $this->render('appointments/index', [
            'title' => 'Appointments',
            'appointments' => $appointments,
            'filter' => $filter,
            'hairdressers' => $hairdressers,
            'adminFilters' => [
                'hairdresser_id' => $hairdresserId,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
            ],
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function show(string $id): string
    {
        $user = $this->requireLogin();
        $isAdmin = (($user['role'] ?? '') === 'admin');

        $idInt = (int)$id;
        if ($idInt <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid appointment']);
        }

        $appointment = $this->appointments->findWithDetails($idInt);

        if ($appointment === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Appointment not found']);
        }

        // Client can only view their own appointment
        $ownerId = (int)($appointment['user_id'] ?? 0);
        if (!$isAdmin && $ownerId !== (int)($user['id'] ?? 0)) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        return $this->render('appointments/show', [
            'title' => 'Appointment Details',
            'appointment' => $appointment,
            'isAdmin' => $isAdmin,
        ]);
    }

    public function create(): string
    {
        $this->requireLogin();

        return $this->render('appointments/create', [
            'title' => 'Book an appointment',
            'hairdressers' => $this->hairdressers->all(),
            'services' => $this->services->all(),
            'errors' => [],
        ]);
    }

    public function confirm(): string
    {
        $this->requireLogin();
        $this->requireCsrf();

        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
        $serviceId     = (int)($_POST['service_id'] ?? 0);
        $dateYmd       = trim((string)($_POST['appointment_date'] ?? ''));
        $timeHi        = trim((string)($_POST['appointment_time'] ?? ''));

        $errors = [];

        if ($hairdresserId <= 0) $errors[] = 'Select a hairdresser.';
        if ($serviceId <= 0) $errors[] = 'Select a service.';

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt === false || $dt->format('Y-m-d') !== $dateYmd) $errors[] = 'Invalid appointment date.';

        $tm = \DateTimeImmutable::createFromFormat('H:i', $timeHi);
        if ($tm === false || $tm->format('H:i') !== $timeHi) $errors[] = 'Invalid appointment time.';

        if ($errors) {
            http_response_code(422);
            return $this->render('appointments/create', [
                'title' => 'Book an appointment',
                'hairdressers' => $this->hairdressers->all(),
                'services' => $this->services->all(),
                'errors' => $errors,
            ]);
        }

        $hairdresser = $this->hairdressers->findById($hairdresserId);
        $service     = $this->services->findById($serviceId);

        if ($hairdresser === null) $errors[] = 'Selected hairdresser not found.';
        if ($service === null) $errors[] = 'Selected service not found.';

        if ($errors) {
            http_response_code(422);
            return $this->render('appointments/create', [
                'title' => 'Book an appointment',
                'hairdressers' => $this->hairdressers->all(),
                'services' => $this->services->all(),
                'errors' => $errors,
            ]);
        }

        return $this->render('appointments/confirm', [
            'title' => 'Confirm Appointment',
            'hairdresser' => $hairdresser,
            'service' => $service,
            'dateYmd' => $dateYmd,
            'timeHi' => $timeHi,
        ]);
    }

    public function finalize(): string
    {
        $user = $this->requireLogin();
        $this->requireCsrf();

        $userId = (int)($user['id'] ?? 0);

        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
        $serviceId     = (int)($_POST['service_id'] ?? 0);
        $dateYmd       = trim((string)($_POST['appointment_date'] ?? ''));
        $timeHi        = trim((string)($_POST['appointment_time'] ?? ''));

        if ($hairdresserId <= 0 || $serviceId <= 0 || $dateYmd === '' || $timeHi === '') {
            http_response_code(422);
            $this->flash('error', 'Missing booking information.');
            return $this->redirect('/appointments/new');
        }

        if ($this->bookingService->overlapsExisting($hairdresserId, $serviceId, $dateYmd, $timeHi)) {
            http_response_code(409);
            $this->flash('error', 'Slot no longer available. Please pick another time.');
            return $this->redirect('/appointments/new');
        }

        $this->appointments->create($hairdresserId, $serviceId, $userId, $dateYmd, $timeHi, 'booked');

        $this->flash('success', 'Appointment booked successfully.');
        return $this->redirect('/appointments');
    }

public function slots(): string
{
    header('Content-Type: application/json; charset=UTF-8');

    try {
        $hairdresserId = filter_input(INPUT_GET, 'hairdresser_id', FILTER_VALIDATE_INT);
        $serviceId     = filter_input(INPUT_GET, 'service_id', FILTER_VALIDATE_INT);

        $dateRaw = trim((string)($_GET['date'] ?? ''));

        if (!$hairdresserId || !$serviceId || $dateRaw === '') {
            http_response_code(400);
            return json_encode([
                'ok' => false,
                'error' => 'Missing or invalid parameters. Required: hairdresser_id, service_id, date (YYYY-MM-DD).',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateRaw);
        $dateValid = $dt && $dt->format('Y-m-d') === $dateRaw;

        if (!$dateValid) {
            http_response_code(400);
            return json_encode([
                'ok' => false,
                'error' => 'Invalid date format. Use YYYY-MM-DD.',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        $slots = $this->availabilityService->availableSlots($hairdresserId, $serviceId, $dateRaw);

        http_response_code(200);
        return json_encode([
            'ok' => true,
            'hairdresser_id' => $hairdresserId,
            'service_id' => $serviceId,
            'date' => $dateRaw,
            'slots' => $slots,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

    } catch (\JsonException $e) {
        http_response_code(500);
        return '{"ok":false,"error":"JSON encoding error"}';
    } catch (\Throwable $e) {
        http_response_code(500);
        return '{"ok":false,"error":"Internal Server Error"}';
    }
}

    public function availabilityApi(): string
    {
        $hairdresserId = (int)($_GET['hairdresser_id'] ?? 0);
        $serviceId = (int)($_GET['service_id'] ?? 0);
        $dateRaw = trim((string)($_GET['date'] ?? $_GET['appointment_date'] ?? ''));

        header('Content-Type: application/json; charset=UTF-8');

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateRaw);
        $dateOk = ($dt !== false && $dt->format('Y-m-d') === $dateRaw);

        if ($hairdresserId <= 0 || $serviceId <= 0 || !$dateOk) {
            http_response_code(400);
            return json_encode([
                'ok' => false,
                'error' => 'Invalid parameters. Expect hairdresser_id, service_id, date (Y-m-d).',
            ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        }

        $slots = $this->availabilityService->availableSlots($hairdresserId, $serviceId, $dateRaw);

        http_response_code(200);
        return json_encode([
            'ok' => true,
            'hairdresser_id' => $hairdresserId,
            'service_id' => $serviceId,
            'date' => $dateRaw,
            'slots' => $slots,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }



    public function cancel(string $id): string
    {
        $user = $this->requireLogin();
        $this->requireCsrf();

        $isAdmin = (($user['role'] ?? '') === 'admin');

        $idInt = (int)$id;
        if ($idInt <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid appointment']);
        }

        $appointment = $this->appointments->findWithDetails($idInt);

        if ($appointment === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Appointment not found']);
        }

        $ownerId = (int)($appointment['user_id'] ?? 0);
        if (!$isAdmin && $ownerId !== (int)($user['id'] ?? 0)) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        if ((string)($appointment['status'] ?? '') !== 'booked') {
            $this->flash('error', 'Only booked appointments can be cancelled.');
            return $this->redirect('/appointments/' . $idInt);
        }

        $this->appointments->cancel($idInt);
        $this->flash('success', 'Appointment cancelled successfully.');

        return $this->redirect('/appointments/' . $idInt);
    }

    public function complete(string $id): string
    {
        $user = $this->requireLogin();
        $this->requireCsrf();

        $isAdmin = (($user['role'] ?? '') === 'admin');
        if (!$isAdmin) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Forbidden']);
        }

        $idInt = (int)$id;
        if ($idInt <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid appointment']);
        }

        $appointment = $this->appointments->findWithDetails($idInt);

        if ($appointment === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Appointment not found']);
        }

        $status = (string)($appointment['status'] ?? '');

        if ($status === 'cancelled') {
            $this->flash('error', 'Cancelled appointments cannot be completed.');
            return $this->redirect('/appointments/' . $idInt);
        }
        if ($status === 'completed') {
            $this->flash('success', 'Appointment already completed.');
            return $this->redirect('/appointments/' . $idInt);
        }

        $ok = $this->appointments->complete($idInt);

        if (!$ok) {
            $this->flash('error', 'Could not complete appointment (it may not be booked anymore).');
            return $this->redirect('/appointments/' . $idInt);
        }

        $this->flash('success', 'Appointment marked as completed.');
        return $this->redirect('/appointments/' . $idInt);
    }

    public function adminCreate(): string
    {
        $this->requireAdmin();

        return $this->render('appointments/admin_form', [
            'title' => 'Admin - Create Appointment',
            'mode' => 'create',
            'errors' => [],
            'clients' => $this->users->allClients(),
            'hairdressers' => $this->hairdressers->all(),
            'services' => $this->services->all(),
            'old' => [
                'user_id' => '',
                'hairdresser_id' => '',
                'service_id' => '',
                'appointment_date' => '',
                'appointment_time' => '',
                'status' => 'booked',
            ],
            'appointmentId' => null,
        ]);
    }

    public function adminStore(): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $userId = (int)($_POST['user_id'] ?? 0);
        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $dateYmd = trim((string)($_POST['appointment_date'] ?? ''));
        $timeHi = trim((string)($_POST['appointment_time'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'booked'));

        $errors = $this->validateAdminAppointmentInput($userId, $hairdresserId, $serviceId, $dateYmd, $timeHi, $status);

        if (!$errors && $status !== 'cancelled' && $this->bookingService->overlapsExisting($hairdresserId, $serviceId, $dateYmd, $timeHi)) {
            $errors[] = 'Selected slot overlaps an existing appointment.';
        }

        if ($errors) {
            http_response_code(422);
            return $this->render('appointments/admin_form', [
                'title' => 'Admin - Create Appointment',
                'mode' => 'create',
                'errors' => $errors,
                'clients' => $this->users->allClients(),
                'hairdressers' => $this->hairdressers->all(),
                'services' => $this->services->all(),
                'old' => [
                    'user_id' => (string)$userId,
                    'hairdresser_id' => (string)$hairdresserId,
                    'service_id' => (string)$serviceId,
                    'appointment_date' => $dateYmd,
                    'appointment_time' => $timeHi,
                    'status' => $status,
                ],
                'appointmentId' => null,
            ]);
        }

        $this->appointments->create($hairdresserId, $serviceId, $userId, $dateYmd, $timeHi, $status);
        $this->flash('success', 'Appointment created by admin.');
        return $this->redirect('/appointments?filter=all');
    }

    public function adminEdit(string $id): string
    {
        $this->requireAdmin();

        $idInt = (int)$id;
        if ($idInt <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid appointment']);
        }

        $appointment = $this->appointments->findWithDetails($idInt);
        if ($appointment === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Appointment not found']);
        }

        return $this->render('appointments/admin_form', [
            'title' => 'Admin - Edit Appointment',
            'mode' => 'edit',
            'errors' => [],
            'clients' => $this->users->allClients(),
            'hairdressers' => $this->hairdressers->all(),
            'services' => $this->services->all(),
            'old' => [
                'user_id' => (string)($appointment['user_id'] ?? ''),
                'hairdresser_id' => (string)($appointment['hairdresser_id'] ?? ''),
                'service_id' => (string)($appointment['service_id'] ?? ''),
                'appointment_date' => (string)($appointment['appointment_date'] ?? ''),
                'appointment_time' => substr((string)($appointment['appointment_time'] ?? ''), 0, 5),
                'status' => (string)($appointment['status'] ?? 'booked'),
            ],
            'appointmentId' => $idInt,
        ]);
    }

    public function adminUpdate(string $id): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $idInt = (int)$id;
        if ($idInt <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid appointment']);
        }

        $existing = $this->appointments->findWithDetails($idInt);
        if ($existing === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Appointment not found']);
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
        $serviceId = (int)($_POST['service_id'] ?? 0);
        $dateYmd = trim((string)($_POST['appointment_date'] ?? ''));
        $timeHi = trim((string)($_POST['appointment_time'] ?? ''));
        $status = trim((string)($_POST['status'] ?? 'booked'));

        $errors = $this->validateAdminAppointmentInput($userId, $hairdresserId, $serviceId, $dateYmd, $timeHi, $status);

        if (!$errors && $status !== 'cancelled' && $this->bookingService->overlapsExisting($hairdresserId, $serviceId, $dateYmd, $timeHi, $idInt)) {
            $errors[] = 'Selected slot overlaps an existing appointment.';
        }

        if ($errors) {
            http_response_code(422);
            return $this->render('appointments/admin_form', [
                'title' => 'Admin - Edit Appointment',
                'mode' => 'edit',
                'errors' => $errors,
                'clients' => $this->users->allClients(),
                'hairdressers' => $this->hairdressers->all(),
                'services' => $this->services->all(),
                'old' => [
                    'user_id' => (string)$userId,
                    'hairdresser_id' => (string)$hairdresserId,
                    'service_id' => (string)$serviceId,
                    'appointment_date' => $dateYmd,
                    'appointment_time' => $timeHi,
                    'status' => $status,
                ],
                'appointmentId' => $idInt,
            ]);
        }

        $this->appointments->updateDetails($idInt, $hairdresserId, $serviceId, $userId, $dateYmd, $timeHi, $status);
        $this->flash('success', 'Appointment updated by admin.');
        return $this->redirect('/appointments?filter=all');
    }

    public function adminDelete(string $id): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $idInt = (int)$id;
        if ($idInt <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid appointment']);
        }

        $this->appointments->deleteById($idInt);
        $this->flash('success', 'Appointment deleted by admin.');
        return $this->redirect('/appointments?filter=all');
    }

    private function validateAdminAppointmentInput(
        int $userId,
        int $hairdresserId,
        int $serviceId,
        string $dateYmd,
        string $timeHi,
        string $status
    ): array {
        $errors = [];

        if ($userId <= 0) $errors[] = 'Select a client.';
        if ($hairdresserId <= 0) $errors[] = 'Select a hairdresser.';
        if ($serviceId <= 0) $errors[] = 'Select a service.';

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt === false || $dt->format('Y-m-d') !== $dateYmd) $errors[] = 'Invalid appointment date.';

        $tm = \DateTimeImmutable::createFromFormat('H:i', $timeHi);
        if ($tm === false || $tm->format('H:i') !== $timeHi) $errors[] = 'Invalid appointment time.';

        if (!in_array($status, ['booked', 'cancelled', 'completed'], true)) {
            $errors[] = 'Invalid status.';
        }

        if (!$errors && $this->users->findById($userId) === null) {
            $errors[] = 'Selected client not found.';
        }

        if (!$errors && $this->hairdressers->findById($hairdresserId) === null) {
            $errors[] = 'Selected hairdresser not found.';
        }

        if (!$errors && $this->services->findById($serviceId) === null) {
            $errors[] = 'Selected service not found.';
        }

        return $errors;
    }

}

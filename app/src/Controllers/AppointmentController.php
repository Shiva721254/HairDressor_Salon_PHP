<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppointmentRepository;
use App\Repositories\HairdresserRepository;
use App\Repositories\ServiceRepository;
use App\Repositories\AvailabilityRepository;

final class AppointmentController extends Controller
{
    public function index(): string
    {
        $user = $this->requireLogin();
        $isAdmin = (($user['role'] ?? '') === 'admin');

        $filter = $_GET['filter'] ?? 'upcoming';
        $filter = is_string($filter) ? strtolower($filter) : 'upcoming';

        $repo = new AppointmentRepository();
        $appointments = $repo->allWithDetails(
            $filter,
            $isAdmin ? null : (int)($user['id'] ?? 0)
        );

        return $this->render('appointments/index', [
            'title' => 'Appointments',
            'appointments' => $appointments,
            'filter' => $filter,
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

        $repo = new AppointmentRepository();
        $appointment = $repo->findWithDetails($idInt);

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

        $hairRepo = new HairdresserRepository();
        $serviceRepo = new ServiceRepository();

        return $this->render('appointments/create', [
            'title' => 'Book an appointment',
            'hairdressers' => $hairRepo->all(),
            'services' => $serviceRepo->all(),
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

        $hairRepo = new HairdresserRepository();
        $serviceRepo = new ServiceRepository();

        if ($errors) {
            http_response_code(422);
            return $this->render('appointments/create', [
                'title' => 'Book an appointment',
                'hairdressers' => $hairRepo->all(),
                'services' => $serviceRepo->all(),
                'errors' => $errors,
            ]);
        }

        $hairdresser = $hairRepo->findById($hairdresserId);
        $service     = $serviceRepo->findById($serviceId);

        if ($hairdresser === null) $errors[] = 'Selected hairdresser not found.';
        if ($service === null) $errors[] = 'Selected service not found.';

        if ($errors) {
            http_response_code(422);
            return $this->render('appointments/create', [
                'title' => 'Book an appointment',
                'hairdressers' => $hairRepo->all(),
                'services' => $serviceRepo->all(),
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

        if ($this->overlapsExisting($hairdresserId, $serviceId, $dateYmd, $timeHi)) {
            http_response_code(409);
            $this->flash('error', 'Slot no longer available. Please pick another time.');
            return $this->redirect('/appointments/new');
        }

        $repo = new AppointmentRepository();
        $repo->create($hairdresserId, $serviceId, $userId, $dateYmd, $timeHi, 'booked');

        $this->flash('success', 'Appointment booked successfully.');
        return $this->redirect('/appointments');
    }

    public function slots(): string
    {
        $hairdresserId = (int)($_GET['hairdresser_id'] ?? 0);
        $serviceId     = (int)($_GET['service_id'] ?? 0);
        $dateYmd       = trim((string)($_GET['date'] ?? ''));

        $errors = [];
        if ($hairdresserId <= 0) $errors[] = 'hairdresser_id required';
        if ($serviceId <= 0) $errors[] = 'service_id required';

        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt === false || $dt->format('Y-m-d') !== $dateYmd) $errors[] = 'valid date required (Y-m-d)';

        if ($errors) {
            return $this->json(['ok' => false, 'errors' => $errors], 422);
        }

        $dayOfWeek = (int)$dt->format('N'); // 1..7

        $availRepo = new AvailabilityRepository();
        $serviceRepo = new ServiceRepository();
        $apptRepo = new AppointmentRepository();

        $window = $availRepo->findWindowFor($hairdresserId, $dayOfWeek);
        $service = $serviceRepo->findById($serviceId);

        if ($window === null || $service === null) {
            return $this->json(['ok' => true, 'slots' => []]);
        }

        $duration = (int)($service['duration_minutes'] ?? 30);
        if ($duration <= 0) $duration = 30;

        $stepMinutes = 15;

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateYmd . ' ' . (string)$window['start_time']);
        $end   = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateYmd . ' ' . (string)$window['end_time']);

        if ($start === false || $end === false || $end <= $start) {
            return $this->json(['ok' => true, 'slots' => []]);
        }

        $bookings = $apptRepo->bookingsForDate($hairdresserId, $dateYmd);

        $slots = [];
        $cursor = $start;
        $latestStart = $end->modify("-{$duration} minutes");

        while ($cursor <= $latestStart) {
            $candidateStart = $cursor;
            $candidateEnd = $candidateStart->modify("+{$duration} minutes");

            $overlaps = false;
            foreach ($bookings as $b) {
                $bStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . (string)($b['start_time'] ?? ''));
                if ($bStart === false) continue;

                $bDur = max(1, (int)($b['duration_minutes'] ?? 0));
                $bEnd = $bStart->modify("+{$bDur} minutes");

                if ($candidateStart < $bEnd && $candidateEnd > $bStart) {
                    $overlaps = true;
                    break;
                }
            }

            if (!$overlaps) {
                $slots[] = $candidateStart->format('H:i');
            }

            $cursor = $cursor->modify("+{$stepMinutes} minutes");
        }

        return $this->json(['ok' => true, 'slots' => $slots]);
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

        $repo = new AppointmentRepository();
        $appointment = $repo->findWithDetails($idInt);

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

        $repo->cancel($idInt);
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

        $repo = new AppointmentRepository();
        $appointment = $repo->findWithDetails($idInt);

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

        $ok = $repo->complete($idInt);

        if (!$ok) {
            $this->flash('error', 'Could not complete appointment (it may not be booked anymore).');
            return $this->redirect('/appointments/' . $idInt);
        }

        $this->flash('success', 'Appointment marked as completed.');
        return $this->redirect('/appointments/' . $idInt);
    }

    private function overlapsExisting(int $hairdresserId, int $serviceId, string $dateYmd, string $timeHi): bool
    {
        $serviceRepo = new ServiceRepository();
        $apptRepo = new AppointmentRepository();

        $service = $serviceRepo->findById($serviceId);
        if ($service === null) return true;

        $duration = max(1, (int)($service['duration_minutes'] ?? 0));

        $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . $timeHi);
        if ($start === false) return true;

        $end = $start->modify("+{$duration} minutes");

        $bookings = $apptRepo->bookingsForDate($hairdresserId, $dateYmd);

        foreach ($bookings as $b) {
            $bStart = \DateTimeImmutable::createFromFormat(
                'Y-m-d H:i',
                $dateYmd . ' ' . (string)($b['start_time'] ?? '')
            );
            if ($bStart === false) continue;

            $bDur = max(1, (int)($b['duration_minutes'] ?? 0));
            $bEnd = $bStart->modify("+{$bDur} minutes");

            if ($start < $bEnd && $end > $bStart) return true;
        }

        return false;
    }
}

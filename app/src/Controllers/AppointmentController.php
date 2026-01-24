<?php
declare(strict_types=1);

namespace App\Controllers;
use App\Core\Controller;

use App\Repositories\AppointmentRepository;
use App\Repositories\HairdresserRepository;
use App\Repositories\ServiceRepository;

final class AppointmentController extends Controller
{
public function index(): string
{
    $repo = new AppointmentRepository();
    $appointments = $repo->allWithDetails();

    $success = $this->flash('success');
    $error = $this->flash('error');

    return $this->render('appointments/index', [
        'title' => 'Appointments',
        'appointments' => $appointments,
        'success' => $success,
        'error' => $error,
    ]);
}   

public function show(string $id): string
{
    $id = (int) $id;

    if ($id <= 0) {
        http_response_code(404);
        return $this->render('errors/404', [
            'title' => 'Invalid appointment'
        ]);
    }

    $repo = new AppointmentRepository();
    $appointment = $repo->findWithDetails($id);

    if ($appointment === null) {
        http_response_code(404);
        return $this->render('errors/404', [
            'title' => 'Appointment not found'
        ]);
    }

    return $this->render('appointments/show', [
        'title' => 'Appointment Details',
        'appointment' => $appointment
    ]);
}

public function create(): void
    {
        $hairRepo = new HairdresserRepository();
        $serviceRepo = new ServiceRepository();

        $hairdressers = $hairRepo->all();
        $services = $serviceRepo->all();

        $errors = [];
        require __DIR__ . '/../../views/appointments/create.php';
    }

    public function store(): void
    {
        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
        $serviceId     = (int)($_POST['service_id'] ?? 0);
        $dateYmd       = trim((string)($_POST['appointment_date'] ?? ''));
        $timeHi        = trim((string)($_POST['appointment_time'] ?? ''));

        // For now: hardcode user id = 1 (until auth is implemented)
        $userId = 1;

        $errors = [];

        if ($hairdresserId <= 0) $errors[] = 'Select a hairdresser.';
        if ($serviceId <= 0) $errors[] = 'Select a service.';

        // Date validation (YYYY-MM-DD)
        $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dt === false || $dt->format('Y-m-d') !== $dateYmd) {
            $errors[] = 'Invalid appointment date.';
        }

        // Time validation (HH:MM)
        $tm = \DateTimeImmutable::createFromFormat('H:i', $timeHi);
        if ($tm === false || $tm->format('H:i') !== $timeHi) {
            $errors[] = 'Invalid appointment time.';
        }

        if ($errors) {
            $hairRepo = new HairdresserRepository();
            $serviceRepo = new ServiceRepository();
            $hairdressers = $hairRepo->all();
            $services = $serviceRepo->all();
            require __DIR__ . '/../../views/appointments/create.php';
            return;
        }

        $repo = new AppointmentRepository();

        // Collision check
        if ($this->overlapsExisting($hairdresserId, $serviceId, $dateYmd, $timeHi)) {
            http_response_code(409);
            $errors[] = 'This hairdresser is already booked for the selected time (overlap).';
            $hairRepo = new HairdresserRepository();
            $serviceRepo = new ServiceRepository();
            $hairdressers = $hairRepo->all();
            $services = $serviceRepo->all();
            require __DIR__ . '/../../views/appointments/create.php';
            return;
        }


        $repo->create($hairdresserId, $serviceId, $userId, $dateYmd, $timeHi, 'booked');

        header('Location: /appointments');
        exit;
    }


    private function overlapsExisting(int $hairdresserId, int $serviceId, string $dateYmd, string $timeHi): bool
{
    $serviceRepo = new \App\Repositories\ServiceRepository();
    $apptRepo = new \App\Repositories\AppointmentRepository();

    $service = $serviceRepo->findById($serviceId);
    if ($service === null) return true;

    $duration = max(1, (int)$service['duration_minutes']);

    $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . $timeHi);
    if ($start === false) return true;
    $end = $start->modify("+{$duration} minutes");

    $bookings = $apptRepo->bookingsForDate($hairdresserId, $dateYmd);

    foreach ($bookings as $b) {
        $bStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . $b['start_time']);
        if ($bStart === false) continue;

        $bDur = max(1, (int)$b['duration_minutes']);
        $bEnd = $bStart->modify("+{$bDur} minutes");

        if ($start < $bEnd && $end > $bStart) return true;
    }

    return false;
}

public function confirm(): void
{
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

    // If validation fails, re-render create page with data
    if ($errors) {
        $hairRepo = new HairdresserRepository();
        $serviceRepo = new ServiceRepository();
        $hairdressers = $hairRepo->all();
        $services = $serviceRepo->all();
        require __DIR__ . '/../../views/appointments/create.php';
        return;
    }

    // Load selected entities for display
    $hairRepo = new HairdresserRepository();
    $serviceRepo = new ServiceRepository();

    $hairdresser = $hairRepo->findById($hairdresserId);
    $service     = $serviceRepo->findById($serviceId);

    if ($hairdresser === null) $errors[] = 'Selected hairdresser not found.';
    if ($service === null) $errors[] = 'Selected service not found.';

    if ($errors) {
        $hairdressers = $hairRepo->all();
        $services = $serviceRepo->all();
        require __DIR__ . '/../../views/appointments/create.php';
        return;
    }

    // Show confirmation page (no DB write yet)
    require __DIR__ . '/../../views/appointments/confirm.php';
}


public function finalize(): void
{
    $hairdresserId = (int)$_POST['hairdresser_id'];
    $serviceId     = (int)$_POST['service_id'];
    $dateYmd       = $_POST['appointment_date'];
    $timeHi        = $_POST['appointment_time'];

    $userId = 1; // until auth

    $repo = new AppointmentRepository();

    // Safety check (race condition protection)
    if ($this->overlapsExisting($hairdresserId, $serviceId, $dateYmd, $timeHi)) {
        http_response_code(409);
        echo 'Slot no longer available.';
        return;
    }

    $repo->create(
        $hairdresserId,
        $serviceId,
        $userId,
        $dateYmd,
        $timeHi,
        'booked'
    );

    header('Location: /appointments');
    exit;
}


public function slots(): void
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
        http_response_code(422);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'errors' => $errors]);
        return;
    }

    $dayOfWeek = (int)$dt->format('N'); // 1..7

    $availRepo = new \App\Repositories\AvailabilityRepository();
    $serviceRepo = new \App\Repositories\ServiceRepository();
    $apptRepo = new \App\Repositories\AppointmentRepository();

    $window = $availRepo->findWindowFor($hairdresserId, $dayOfWeek);
    $service = $serviceRepo->findById($serviceId);

    if ($window === null || $service === null) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'slots' => []]);
        return;
    }

    $duration = (int)$service['duration_minutes'];
    if ($duration <= 0) $duration = 30;

    // 15-minute increments (typical booking UX)
    $stepMinutes = 15;

    $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateYmd . ' ' . $window['start_time']);
    $end   = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $dateYmd . ' ' . $window['end_time']);

    if ($start === false || $end === false || $end <= $start) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => true, 'slots' => []]);
        return;
    }

    // Existing bookings with their durations
    $bookings = $apptRepo->bookingsForDate($hairdresserId, $dateYmd);

    $slots = [];
    $cursor = $start;
    $latestStart = $end->modify("-{$duration} minutes");

    while ($cursor <= $latestStart) {
        $candidateStart = $cursor;
        $candidateEnd = $candidateStart->modify("+{$duration} minutes");

        $overlaps = false;
        foreach ($bookings as $b) {
            $bStart = \DateTimeImmutable::createFromFormat('Y-m-d H:i', $dateYmd . ' ' . $b['start_time']);
            if ($bStart === false) continue;

            $bDur = max(1, (int)$b['duration_minutes']);
            $bEnd = $bStart->modify("+{$bDur} minutes");

            // overlap if candidateStart < bEnd AND candidateEnd > bStart
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

    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'slots' => $slots]);
}


   public function cancel(string $id): string
    {
        $id = (int)$id;

        if ($id <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid appointment']);
        }

        $repo = new AppointmentRepository();
        $appointment = $repo->findWithDetails($id);

        if ($appointment === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Appointment not found']);
        }

        if (($appointment['status'] ?? '') === 'cancelled') {
            $this->flash('success', 'Appointment already cancelled.');
            return $this->redirect('/appointments/' . $id);
        }

        $repo->cancel($id);
        $this->flash('success', 'Appointment cancelled successfully.');

        return $this->redirect('/appointments/' . $id);
    }


}

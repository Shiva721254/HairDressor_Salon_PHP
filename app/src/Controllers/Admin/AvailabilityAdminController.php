<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\AvailabilityRepository;
use App\Repositories\HairdresserRepository;

final class AvailabilityAdminController extends Controller
{
    private function ensureAdminOrForbidden(): ?array
    {
        $user = $this->requireLogin();

        if (($user['role'] ?? '') !== 'admin') {
            http_response_code(403);
            // Return null means caller should return forbidden view.
            return null;
        }

        return $user;
    }

    private function ensureCsrfToken(): void
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    private function isValidCsrf(): bool
    {
        $this->ensureCsrfToken();

        $token   = (string)($_POST['csrf_token'] ?? '');
        $session = (string)($_SESSION['csrf_token'] ?? '');

        return $token !== '' && $session !== '' && hash_equals($session, $token);
    }

    private function renderForbidden(): string
    {
        return $this->render('errors/403', ['title' => 'Forbidden']);
    }

    public function index(): string
    {
        $user = $this->ensureAdminOrForbidden();
        if ($user === null) return $this->renderForbidden();

        $repo = new AvailabilityRepository();
        $rows = $repo->allWithHairdresserNames();

        // Group per hairdresser for nicer UI
        $grouped = [];
        foreach ($rows as $r) {
            $hid = (int)$r['hairdresser_id'];

            if (!isset($grouped[$hid])) {
                $grouped[$hid] = [
                    'hairdresser_id' => $hid,
                    'hairdresser_name' => (string)$r['hairdresser_name'],
                    'items' => [],
                ];
            }

            $grouped[$hid]['items'][] = $r;
        }

        return $this->render('admin/availability/index', [
            'title' => 'Admin - Availability',
            'groups' => array_values($grouped),
            'success' => $this->flash('success'),
            'error' => $this->flash('error'),
        ]);
    }

    public function create(): string
    {
        $user = $this->ensureAdminOrForbidden();
        if ($user === null) return $this->renderForbidden();

        $this->ensureCsrfToken();

        $hairRepo = new HairdresserRepository();

        return $this->render('admin/availability/create', [
            'title' => 'Add Availability',
            'hairdressers' => $hairRepo->all(),
            'errors' => [],
            'old' => [
                'hairdresser_id' => '',
                'day_of_week' => '1',
                'start_time' => '09:00',
                'end_time' => '17:00',
            ],
        ]);
    }

    public function store(): string
    {
        $user = $this->ensureAdminOrForbidden();
        if ($user === null) return $this->renderForbidden();

        if (!$this->isValidCsrf()) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Invalid CSRF token']);
        }

        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
        $dayOfWeek     = (int)($_POST['day_of_week'] ?? 0);
        $startTime     = trim((string)($_POST['start_time'] ?? ''));
        $endTime       = trim((string)($_POST['end_time'] ?? ''));

        $errors = [];

        if ($hairdresserId <= 0) $errors[] = 'Select a hairdresser.';
        if ($dayOfWeek < 1 || $dayOfWeek > 7) $errors[] = 'Select a valid day of week (1..7).';

        $st = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        if ($st === false || $st->format('H:i') !== $startTime) $errors[] = 'Start time must be HH:MM.';

        $et = \DateTimeImmutable::createFromFormat('H:i', $endTime);
        if ($et === false || $et->format('H:i') !== $endTime) $errors[] = 'End time must be HH:MM.';

        if (empty($errors) && $startTime >= $endTime) {
            $errors[] = 'End time must be after start time.';
        }

        $hairRepo = new HairdresserRepository();
        if (empty($errors) && $hairRepo->findById($hairdresserId) === null) {
            $errors[] = 'Selected hairdresser not found.';
        }

        $repo = new AvailabilityRepository();

        // Prevent overlapping windows for same hairdresser/day
        if (empty($errors) && $repo->overlapsWindow($hairdresserId, $dayOfWeek, $startTime, $endTime)) {
            $errors[] = 'This availability overlaps with an existing window for that hairdresser/day.';
        }

        if ($errors) {
            http_response_code(422);
            return $this->render('admin/availability/create', [
                'title' => 'Add Availability',
                'hairdressers' => $hairRepo->all(),
                'errors' => $errors,
                'old' => [
                    'hairdresser_id' => (string)$hairdresserId,
                    'day_of_week' => (string)$dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
            ]);
        }

        $repo->create($hairdresserId, $dayOfWeek, $startTime, $endTime);

        $this->flash('success', 'Availability window created.');
        return $this->redirect('/admin/availability');
    }

    public function edit(string $id): string
    {
        $user = $this->ensureAdminOrForbidden();
        if ($user === null) return $this->renderForbidden();

        $this->ensureCsrfToken();

        $aid = (int)$id;
        if ($aid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $repo = new AvailabilityRepository();
        $row = $repo->findByIdWithHairdresserName($aid);

        if ($row === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $hairRepo = new HairdresserRepository();

        return $this->render('admin/availability/edit', [
            'title' => 'Edit Availability',
            'hairdressers' => $hairRepo->all(),
            'errors' => [],
            'row' => $row,
        ]);
    }

    public function update(string $id): string
    {
        $user = $this->ensureAdminOrForbidden();
        if ($user === null) return $this->renderForbidden();

        if (!$this->isValidCsrf()) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Invalid CSRF token']);
        }

        $aid = (int)$id;
        if ($aid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $repo = new AvailabilityRepository();
        $existing = $repo->findByIdWithHairdresserName($aid);

        if ($existing === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $hairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
        $dayOfWeek     = (int)($_POST['day_of_week'] ?? 0);
        $startTime     = trim((string)($_POST['start_time'] ?? ''));
        $endTime       = trim((string)($_POST['end_time'] ?? ''));

        $errors = [];

        if ($hairdresserId <= 0) $errors[] = 'Select a hairdresser.';
        if ($dayOfWeek < 1 || $dayOfWeek > 7) $errors[] = 'Select a valid day of week (1..7).';

        $st = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        if ($st === false || $st->format('H:i') !== $startTime) $errors[] = 'Start time must be HH:MM.';

        $et = \DateTimeImmutable::createFromFormat('H:i', $endTime);
        if ($et === false || $et->format('H:i') !== $endTime) $errors[] = 'End time must be HH:MM.';

        if (empty($errors) && $startTime >= $endTime) {
            $errors[] = 'End time must be after start time.';
        }

        $hairRepo = new HairdresserRepository();
        if (empty($errors) && $hairRepo->findById($hairdresserId) === null) {
            $errors[] = 'Selected hairdresser not found.';
        }

        if (empty($errors) && $repo->overlapsWindow($hairdresserId, $dayOfWeek, $startTime, $endTime, $aid)) {
            $errors[] = 'This availability overlaps with an existing window for that hairdresser/day.';
        }

        if ($errors) {
            http_response_code(422);

            // Override existing for form re-display (keep HH:MM)
            $existing['hairdresser_id'] = $hairdresserId;
            $existing['day_of_week'] = $dayOfWeek;
            $existing['start_time'] = $startTime;
            $existing['end_time'] = $endTime;

            return $this->render('admin/availability/edit', [
                'title' => 'Edit Availability',
                'hairdressers' => $hairRepo->all(),
                'errors' => $errors,
                'row' => $existing,
            ]);
        }

        $repo->update($aid, $hairdresserId, $dayOfWeek, $startTime, $endTime);

        $this->flash('success', 'Availability window updated.');
        return $this->redirect('/admin/availability');
    }

    public function delete(string $id): string
    {
        $user = $this->ensureAdminOrForbidden();
        if ($user === null) return $this->renderForbidden();

        if (!$this->isValidCsrf()) {
            http_response_code(403);
            return $this->render('errors/403', ['title' => 'Invalid CSRF token']);
        }

        $aid = (int)$id;
        if ($aid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $repo = new AvailabilityRepository();
        $repo->delete($aid);

        $this->flash('success', 'Availability window deleted.');
        return $this->redirect('/admin/availability');
    }
}

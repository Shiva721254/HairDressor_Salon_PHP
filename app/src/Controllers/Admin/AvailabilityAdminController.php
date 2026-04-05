<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Repositories\AvailabilityRepositoryInterface;
use App\Repositories\HairdresserRepositoryInterface;

final class AvailabilityAdminController extends Controller
{
    public function __construct(
        private AvailabilityRepositoryInterface $availability,
        private HairdresserRepositoryInterface $hairdressers
    ) {
    }

    private function requireAdmin(): void
    {
        $this->requireRole('admin');
    }

    /**
     * @return array{hairdresser_id:int,day_of_week:int,start_time:string,end_time:string}
     */
    private function readAvailabilityInput(): array
    {
        return [
            'hairdresser_id' => (int)($_POST['hairdresser_id'] ?? 0),
            'day_of_week' => (int)($_POST['day_of_week'] ?? 0),
            'start_time' => trim((string)($_POST['start_time'] ?? '')),
            'end_time' => trim((string)($_POST['end_time'] ?? '')),
        ];
    }

    /**
     * @return array<int, string>
     */
    private function validateAvailabilityInput(
        int $hairdresserId,
        int $dayOfWeek,
        string $startTime,
        string $endTime,
        ?int $excludeAvailabilityId = null
    ): array {
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

        if (empty($errors) && $this->hairdressers->findById($hairdresserId) === null) {
            $errors[] = 'Selected hairdresser not found.';
        }

        if (
            empty($errors)
            && $this->availability->overlapsWindow($hairdresserId, $dayOfWeek, $startTime, $endTime, $excludeAvailabilityId)
        ) {
            $errors[] = 'This availability overlaps with an existing window for that hairdresser/day.';
        }

        return $errors;
    }

    public function index(): string
    {
        $this->requireAdmin();

        $rows = $this->availability->allWithHairdresserNames();

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
        $this->requireAdmin();
        $this->csrfToken();

        return $this->render('admin/availability/create', [
            'title' => 'Add Availability',
            'hairdressers' => $this->hairdressers->all(),
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
        $this->requireAdmin();
        $this->requireCsrf();

        $input = $this->readAvailabilityInput();
        $hairdresserId = $input['hairdresser_id'];
        $dayOfWeek = $input['day_of_week'];
        $startTime = $input['start_time'];
        $endTime = $input['end_time'];

        $errors = $this->validateAvailabilityInput($hairdresserId, $dayOfWeek, $startTime, $endTime);

        if ($errors) {
            http_response_code(422);
            return $this->render('admin/availability/create', [
                'title' => 'Add Availability',
                'hairdressers' => $this->hairdressers->all(),
                'errors' => $errors,
                'old' => [
                    'hairdresser_id' => (string)$hairdresserId,
                    'day_of_week' => (string)$dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
            ]);
        }

        $this->availability->create($hairdresserId, $dayOfWeek, $startTime, $endTime);

        $this->flash('success', 'Availability window created.');
        return $this->redirect('/admin/availability');
    }

    public function edit(string $id): string
    {
        $this->requireAdmin();
        $this->csrfToken();

        $aid = (int)$id;
        if ($aid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $row = $this->availability->findByIdWithHairdresserName($aid);

        if ($row === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        return $this->render('admin/availability/edit', [
            'title' => 'Edit Availability',
            'hairdressers' => $this->hairdressers->all(),
            'errors' => [],
            'row' => $row,
        ]);
    }

    public function update(string $id): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $aid = (int)$id;
        if ($aid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $existing = $this->availability->findByIdWithHairdresserName($aid);

        if ($existing === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $input = $this->readAvailabilityInput();
        $hairdresserId = $input['hairdresser_id'];
        $dayOfWeek = $input['day_of_week'];
        $startTime = $input['start_time'];
        $endTime = $input['end_time'];

        $errors = $this->validateAvailabilityInput($hairdresserId, $dayOfWeek, $startTime, $endTime, $aid);

        if ($errors) {
            http_response_code(422);

            // Override existing for form re-display (keep HH:MM)
            $existing['hairdresser_id'] = $hairdresserId;
            $existing['day_of_week'] = $dayOfWeek;
            $existing['start_time'] = $startTime;
            $existing['end_time'] = $endTime;

            return $this->render('admin/availability/edit', [
                'title' => 'Edit Availability',
                'hairdressers' => $this->hairdressers->all(),
                'errors' => $errors,
                'row' => $existing,
            ]);
        }

        $this->availability->update($aid, $hairdresserId, $dayOfWeek, $startTime, $endTime);

        $this->flash('success', 'Availability window updated.');
        return $this->redirect('/admin/availability');
    }

    public function delete(string $id): string
    {
        $this->requireAdmin();
        $this->requireCsrf();

        $aid = (int)$id;
        if ($aid <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability not found']);
        }

        $this->availability->delete($aid);

        $this->flash('success', 'Availability window deleted.');
        return $this->redirect('/admin/availability');
    }
}

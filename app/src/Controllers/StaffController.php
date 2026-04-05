<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Repositories\AppointmentRepositoryInterface;
use App\Repositories\AvailabilityRepositoryInterface;

final class StaffController extends Controller
{
    public function __construct(
        private AppointmentRepositoryInterface $appointments,
        private AvailabilityRepositoryInterface $availability
    ) {
    }

    /**
     * @return array{
     *   weekly:array<int, array<string,mixed>>,
     *   blocked:array<int, array<string,mixed>>,
     *   blockedThisWeek:array<int, array<string,mixed>>,
     *   weekStart:string,
     *   weekEnd:string,
     *   weeklyOverview:array<int, array<string,mixed>>
     * }
     */
    private function availabilityContext(int $hairdresserId): array
    {
        $weekly = $this->availability->allWeeklyForHairdresser($hairdresserId);
        $blocked = $this->availability->allBlockedForHairdresser($hairdresserId);
        $weekStart = (new \DateTimeImmutable('today'))->format('Y-m-d');
        $weekEnd = (new \DateTimeImmutable('today'))->modify('+7 days')->format('Y-m-d');
        $blockedThisWeek = array_values(array_filter(
            $blocked,
            static fn(array $slot): bool =>
                ((string)($slot['slot_date'] ?? '')) >= $weekStart &&
                ((string)($slot['slot_date'] ?? '')) <= $weekEnd
        ));

        return [
            'weekly' => $weekly,
            'blocked' => $blocked,
            'blockedThisWeek' => $blockedThisWeek,
            'weekStart' => $weekStart,
            'weekEnd' => $weekEnd,
            'weeklyOverview' => $this->buildWeeklyOverview($weekly, $blocked, $weekStart, $weekEnd),
        ];
    }

    /** @param array<string,mixed> $overrides */
    private function renderAvailabilityPage(int $hairdresserId, array $overrides = [], int $statusCode = 200): string
    {
        http_response_code($statusCode);
        return $this->render('staff/availability', array_merge(
            [
                'title' => 'Staff - My Availability',
                'errors' => [],
            ],
            $this->availabilityContext($hairdresserId),
            $overrides
        ));
    }

    private function requireStaffWithHairdresser(): array
    {
        $user = $this->currentUser();
        if ($user === null) {
            $this->flash('error', 'Please login as staff first.');
            $this->redirect('/staff/login');
        }

        if (($user['role'] ?? '') !== 'staff') {
            http_response_code(403);
            echo $this->render('errors/403', ['title' => 'Forbidden']);
            exit;
        }

        $hairdresserId = (int)($user['hairdresser_id'] ?? 0);

        if ($hairdresserId <= 0) {
            http_response_code(403);
            echo $this->render('errors/403', ['title' => 'Staff account is not linked to a hairdresser profile']);
            exit;
        }

        return $user;
    }

    public function appointments(): string
    {
        $user = $this->requireStaffWithHairdresser();
        $hairdresserId = (int)$user['hairdresser_id'];

        $filter = isset($_GET['filter']) ? strtolower(trim((string)$_GET['filter'])) : 'upcoming';
        if (!in_array($filter, ['upcoming', 'all', 'completed', 'cancelled'], true)) {
            $filter = 'upcoming';
        }

        $rows = $this->appointments->allWithDetails($filter, null, $hairdresserId, null, null);

        return $this->render('staff/appointments', [
            'title' => 'Staff - My Appointments',
            'appointments' => $rows,
            'filter' => $filter,
        ]);
    }

    public function availability(): string
    {
        $user = $this->requireStaffWithHairdresser();
        $hairdresserId = (int)$user['hairdresser_id'];
        $weekly = $this->availability->allWeeklyForHairdresser($hairdresserId);
        $blocked = $this->availability->allBlockedForHairdresser($hairdresserId);

        $editWindowId = (int)($_GET['edit_weekly_id'] ?? 0);
        $weeklyForm = [
            'day_of_week' => '',
            'start_time' => '',
            'end_time' => '',
        ];
        $editingWindow = null;

        if ($editWindowId > 0) {
            $editingWindow = $this->availability->findWeeklyForHairdresserById($editWindowId, $hairdresserId);
            if ($editingWindow !== null) {
                $weeklyForm = [
                    'day_of_week' => (string)($editingWindow['day_of_week'] ?? ''),
                    'start_time' => (string)($editingWindow['start_time'] ?? ''),
                    'end_time' => (string)($editingWindow['end_time'] ?? ''),
                ];
            }
        }

        $weekStart = new \DateTimeImmutable('today');
        $weekEnd = $weekStart->modify('+7 days');
        $weekStartYmd = $weekStart->format('Y-m-d');
        $weekEndYmd = $weekEnd->format('Y-m-d');

        $blockedThisWeek = array_values(array_filter(
            $blocked,
            static function (array $slot) use ($weekStartYmd, $weekEndYmd): bool {
                $d = (string)($slot['slot_date'] ?? '');
                return $d >= $weekStartYmd && $d <= $weekEndYmd;
            }
        ));

        $weeklyOverview = $this->buildWeeklyOverview($weekly, $blocked, $weekStartYmd, $weekEndYmd);

        $adjustDate = trim((string)($_GET['adjust_date'] ?? ''));
        $adjustWindowId = (int)($_GET['adjust_window_id'] ?? 0);
        $adjustmentForm = null;

        if ($adjustDate !== '' && $adjustWindowId > 0) {
            foreach ($weeklyOverview as $row) {
                if ((string)($row['date'] ?? '') === $adjustDate && (int)($row['window_id'] ?? 0) === $adjustWindowId) {
                    $adjustmentForm = [
                        'date' => $adjustDate,
                        'window_id' => $adjustWindowId,
                        'start_time' => (string)($row['base_start_time'] ?? ''),
                        'end_time' => (string)($row['start_time'] ?? ''),
                        'note' => (string)($row['status_note'] ?? ''),
                        'slot_id' => (int)($row['adjusted_slot_id'] ?? 0),
                    ];
                    break;
                }
            }
        }

        return $this->render('staff/availability', [
            'title' => 'Staff - My Availability',
            'weekly' => $weekly,
            'blocked' => $blocked,
            'blockedThisWeek' => $blockedThisWeek,
            'weekStart' => $weekStartYmd,
            'weekEnd' => $weekEndYmd,
            'weeklyOverview' => $weeklyOverview,
            'adjustmentForm' => $adjustmentForm,
            'editingWindow' => $editingWindow,
            'weeklyForm' => $weeklyForm,
            'errors' => [],
        ]);
    }

    public function storeWeeklyAvailability(): string
    {
        $user = $this->requireStaffWithHairdresser();
        $this->requireCsrf();

        $hairdresserId = (int)$user['hairdresser_id'];
        $dayOfWeek = (int)($_POST['day_of_week'] ?? 0);
        $startTime = trim((string)($_POST['start_time'] ?? ''));
        $endTime = trim((string)($_POST['end_time'] ?? ''));

        $errors = $this->validateWeeklyInput($dayOfWeek, $startTime, $endTime);

        $existingForDay = null;
        if (!$errors) {
            $existingForDay = $this->availability->findWeeklyForHairdresserDay($hairdresserId, $dayOfWeek);
        }

        if (!$errors && $existingForDay !== null) {
            $existingId = (int)($existingForDay['id'] ?? 0);
            if (
                $existingId > 0
                && $this->availability->overlapsWindow($hairdresserId, $dayOfWeek, $startTime, $endTime, $existingId)
            ) {
                $errors[] = 'This weekly window overlaps another existing window. Use Edit/Delete in Weekly Overview.';
            }
        } elseif (!$errors && $this->availability->overlapsWindow($hairdresserId, $dayOfWeek, $startTime, $endTime)) {
            $errors[] = 'This weekly window overlaps an existing one.';
        }

        if ($errors) {
            return $this->renderAvailabilityPage($hairdresserId, [
                'weeklyForm' => [
                    'day_of_week' => (string)$dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                'errors' => $errors,
            ], 422);
        }

        if ($existingForDay !== null && (int)($existingForDay['id'] ?? 0) > 0) {
            $this->availability->updateForHairdresser((int)$existingForDay['id'], $hairdresserId, $dayOfWeek, $startTime, $endTime);
            $this->flash('success', 'Weekly availability window updated.');
        } else {
            $this->availability->create($hairdresserId, $dayOfWeek, $startTime, $endTime);
            $this->flash('success', 'Weekly availability window added.');
        }
        return $this->redirect('/staff/availability');
    }

    public function updateWeeklyAvailability(string $id): string
    {
        $user = $this->requireStaffWithHairdresser();
        $this->requireCsrf();

        $hairdresserId = (int)$user['hairdresser_id'];
        $windowId = (int)$id;

        if ($windowId <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid availability window']);
        }

        $existing = $this->availability->findWeeklyForHairdresserById($windowId, $hairdresserId);
        if ($existing === null) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Availability window not found']);
        }

        $dayOfWeek = (int)($_POST['day_of_week'] ?? 0);
        $startTime = trim((string)($_POST['start_time'] ?? ''));
        $endTime = trim((string)($_POST['end_time'] ?? ''));

        $errors = $this->validateWeeklyInput($dayOfWeek, $startTime, $endTime);

        if (!$errors && $this->availability->overlapsWindow($hairdresserId, $dayOfWeek, $startTime, $endTime, $windowId)) {
            $errors[] = 'This weekly window overlaps an existing one.';
        }

        if ($errors) {
            return $this->renderAvailabilityPage($hairdresserId, [
                'editingWindow' => [
                    'id' => $windowId,
                    'day_of_week' => $dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                'weeklyForm' => [
                    'day_of_week' => (string)$dayOfWeek,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                ],
                'errors' => $errors,
            ], 422);
        }

        $this->availability->updateForHairdresser($windowId, $hairdresserId, $dayOfWeek, $startTime, $endTime);
        $this->flash('success', 'Weekly availability window updated.');
        return $this->redirect('/staff/availability');
    }

    public function deleteWeeklyAvailability(string $id): string
    {
        $user = $this->requireStaffWithHairdresser();
        $this->requireCsrf();

        $hairdresserId = (int)$user['hairdresser_id'];
        $windowId = (int)$id;
        if ($windowId <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid availability window']);
        }

        $this->availability->deleteForHairdresser($windowId, $hairdresserId);
        $this->flash('success', 'Weekly availability window removed.');
        return $this->redirect('/staff/availability');
    }

    public function storeBlockedSlot(): string
    {
        $user = $this->requireStaffWithHairdresser();
        $this->requireCsrf();

        $hairdresserId = (int)$user['hairdresser_id'];
        $dateYmd = trim((string)($_POST['slot_date'] ?? ''));
        $startTime = trim((string)($_POST['start_time'] ?? ''));
        $endTime = trim((string)($_POST['end_time'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));

        $errors = [];
        $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dateObj === false || $dateObj->format('Y-m-d') !== $dateYmd) $errors[] = 'Invalid date.';

        $startObj = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        $endObj = \DateTimeImmutable::createFromFormat('H:i', $endTime);
        if ($startObj === false || $startObj->format('H:i') !== $startTime) $errors[] = 'Invalid start time.';
        if ($endObj === false || $endObj->format('H:i') !== $endTime) $errors[] = 'Invalid end time.';
        if (!$errors && $startObj >= $endObj) $errors[] = 'End time must be after start time.';

        if (!$errors) {
            $startMinute = (int)$startObj->format('i');
            $endMinute = (int)$endObj->format('i');
            if (!in_array($startMinute, [0, 15, 30, 45], true) || !in_array($endMinute, [0, 15, 30, 45], true)) {
                $errors[] = 'Use quarter-hour times only (e.g. 09:00, 09:15, 09:30, 09:45).';
            }
        }

        if (!$errors && !$this->isWithinBusinessHours($startObj, $endObj)) {
            $errors[] = 'Business hours are 08:00 to 17:00.';
        }

        if (!$errors) {
            $today = new \DateTimeImmutable('today');
            if ($dateObj < $today) {
                $errors[] = 'Cannot block slots in the past.';
            } elseif ($dateObj->format('Y-m-d') === $today->format('Y-m-d')) {
                $nowHi = (new \DateTimeImmutable('now'))->format('H:i');
                if ($endTime <= $nowHi) {
                    $errors[] = 'Cannot block a slot that already ended.';
                }
            }
        }

        if (!$errors) {
            $dayOfWeek = (int)$dateObj->format('N');
            $weekly = $this->availability->allWeeklyForHairdresser($hairdresserId);

            $startMinutes = $this->timeToMinutes($startTime);
            $endMinutes = $this->timeToMinutes($endTime);

            $covered = false;
            foreach ($weekly as $w) {
                if ((int)($w['day_of_week'] ?? 0) !== $dayOfWeek) {
                    continue;
                }

                $wStart = $this->timeToMinutes((string)($w['start_time'] ?? '00:00'));
                $wEnd = $this->timeToMinutes((string)($w['end_time'] ?? '00:00'));

                if ($startMinutes >= $wStart && $endMinutes <= $wEnd) {
                    $covered = true;
                    break;
                }
            }

            if (!$covered) {
                $errors[] = 'Blocked slot must be inside your weekly availability window for that day.';
            }
        }

        if (!$errors && $this->availability->overlapsBlockedSlot($hairdresserId, $dateYmd, $startTime, $endTime)) {
            $errors[] = 'This blocked slot overlaps an existing blocked slot.';
        }

        if ($errors) {
            return $this->renderAvailabilityPage($hairdresserId, [
                'errors' => $errors,
            ], 422);
        }

        $this->availability->createBlockedSlot($hairdresserId, $dateYmd, $startTime, $endTime, $note !== '' ? $note : null);

        $weekStart = new \DateTimeImmutable('today');
        $weekEnd = $weekStart->modify('+7 days');
        $isCurrentWeek = ($dateObj >= $weekStart && $dateObj <= $weekEnd);

        if ($isCurrentWeek) {
            $this->flash('success', 'Slot blocked and applied immediately for this week.');
        } else {
            $this->flash('success', 'Specific time slot marked as unavailable.');
        }

        return $this->redirect('/staff/availability');
    }

    public function adjustOverview(): string
    {
        $user = $this->requireStaffWithHairdresser();
        $this->requireCsrf();

        $hairdresserId = (int)$user['hairdresser_id'];
        $dateYmd = trim((string)($_POST['date'] ?? ''));
        $windowId = (int)($_POST['window_id'] ?? 0);
        $slotId = (int)($_POST['slot_id'] ?? 0);
        $startTime = trim((string)($_POST['start_time'] ?? ''));
        $endTime = trim((string)($_POST['end_time'] ?? ''));
        $note = trim((string)($_POST['note'] ?? ''));

        $errors = [];
        $dateObj = \DateTimeImmutable::createFromFormat('Y-m-d', $dateYmd);
        if ($dateObj === false || $dateObj->format('Y-m-d') !== $dateYmd) {
            $errors[] = 'Invalid date.';
        }

        $startObj = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        $endObj = \DateTimeImmutable::createFromFormat('H:i', $endTime);
        if ($startObj === false || $startObj->format('H:i') !== $startTime) $errors[] = 'Invalid start time.';
        if ($endObj === false || $endObj->format('H:i') !== $endTime) $errors[] = 'Invalid end time.';
        if (!$errors && $startObj >= $endObj) $errors[] = 'End time must be after start time.';
        if (!$errors && (!$this->isQuarterHour($startObj) || !$this->isQuarterHour($endObj))) {
            $errors[] = 'Use quarter-hour times only (minutes must be 00, 15, 30, or 45).';
        }
        if (!$errors && !$this->isWithinBusinessHours($startObj, $endObj)) {
            $errors[] = 'Business hours are 08:00 to 17:00.';
        }

        $window = null;
        if (!$errors) {
            $window = $this->availability->findWeeklyForHairdresserById($windowId, $hairdresserId);
            if ($window === null) {
                $errors[] = 'Weekly window not found.';
            }
        }

        if (!$errors && $window !== null) {
            $dow = (int)$dateObj->format('N');
            if ((int)($window['day_of_week'] ?? 0) !== $dow) {
                $errors[] = 'Selected date does not match the weekly window day.';
            }
        }

        if (!$errors && $window !== null) {
            $wStart = $this->timeToMinutes((string)($window['start_time'] ?? '00:00'));
            $wEnd = $this->timeToMinutes((string)($window['end_time'] ?? '00:00'));
            $s = $this->timeToMinutes($startTime);
            $e = $this->timeToMinutes($endTime);
            if (!($s >= $wStart && $e <= $wEnd)) {
                $errors[] = 'Adjustment must stay inside that day\'s weekly window.';
            }
        }

        if ($errors) {
            $this->flash('error', implode(' ', $errors));
            return $this->redirect('/staff/availability?adjust_date=' . rawurlencode($dateYmd) . '&adjust_window_id=' . $windowId);
        }

        $existing = $slotId > 0 ? $this->availability->findBlockedForHairdresserById($slotId, $hairdresserId) : null;

        if ($existing !== null) {
            $this->availability->updateBlockedSlotForHairdresser(
                $slotId,
                $hairdresserId,
                $dateYmd,
                $startTime,
                $endTime,
                $note !== '' ? $note : null
            );
        } else {
            $this->availability->createBlockedSlot(
                $hairdresserId,
                $dateYmd,
                $startTime,
                $endTime,
                $note !== '' ? $note : null
            );
        }

        $this->flash('success', 'Overview day adjusted.');
        return $this->redirect('/staff/availability');
    }

    public function clearOverviewAdjustment(string $slotId): string
    {
        $user = $this->requireStaffWithHairdresser();
        $this->requireCsrf();

        $hairdresserId = (int)$user['hairdresser_id'];
        $id = (int)$slotId;
        if ($id <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid adjustment']);
        }

        $this->availability->deleteBlockedSlot($id, $hairdresserId);
        $this->flash('success', 'Overview adjustment cleared.');
        return $this->redirect('/staff/availability');
    }

    private function timeToMinutes(string $timeHi): int
    {
        [$h, $m] = explode(':', $timeHi . ':0');
        return ((int)$h * 60) + (int)$m;
    }

    private function isQuarterHour(\DateTimeImmutable $time): bool
    {
        return in_array((int)$time->format('i'), [0, 15, 30, 45], true);
    }

    private function validateWeeklyInput(int $dayOfWeek, string $startTime, string $endTime): array
    {
        $errors = [];
        if ($dayOfWeek < 1 || $dayOfWeek > 7) $errors[] = 'Day of week must be between 1 and 7.';

            // Prevent Saturday and Sunday
            if ($dayOfWeek === 6 || $dayOfWeek === 7) {
                $errors[] = 'Salon is closed on weekends (Saturday & Sunday).';
            }

        $startObj = \DateTimeImmutable::createFromFormat('H:i', $startTime);
        $endObj = \DateTimeImmutable::createFromFormat('H:i', $endTime);

        if ($startObj === false || $startObj->format('H:i') !== $startTime) $errors[] = 'Invalid start time.';
        if ($endObj === false || $endObj->format('H:i') !== $endTime) $errors[] = 'Invalid end time.';
        if (!$errors && $startObj >= $endObj) $errors[] = 'End time must be after start time.';
        if (!$errors && (!$this->isQuarterHour($startObj) || !$this->isQuarterHour($endObj))) {
            $errors[] = 'Use quarter-hour times only (minutes must be 00, 15, 30, or 45).';
        }

        if (!$errors && !$this->isWithinBusinessHours($startObj, $endObj)) {
            $errors[] = 'Business hours are 08:00 to 17:00.';
        }

        return $errors;
    }

    /**
     * @param array<int, array<string,mixed>> $weekly
     * @param array<int, array<string,mixed>> $blocked
     * @return array<int, array{date:string,day_name:string,base_start_time:string,start_time:string,end_time:string,status:string,status_note:string,window_id:int,adjusted_slot_id:int}>
     */
    private function buildWeeklyOverview(array $weekly, array $blocked, string $fromYmd, string $toYmd): array
    {
        $blockedByDate = [];
        foreach ($blocked as $b) {
            $d = (string)($b['slot_date'] ?? '');
            $bs = $this->timeToMinutes((string)($b['start_time'] ?? '00:00'));
            $be = $this->timeToMinutes((string)($b['end_time'] ?? '00:00'));
            if ($d === '' || $bs >= $be) {
                continue;
            }
            $blockedByDate[$d][] = [
                'id' => (int)($b['id'] ?? 0),
                'start' => $bs,
                'end' => $be,
                'note' => (string)($b['note'] ?? ''),
            ];
        }

        $rows = [];
        $from = new \DateTimeImmutable($fromYmd);
        $to = new \DateTimeImmutable($toYmd);

        for ($d = $from; $d <= $to; $d = $d->modify('+1 day')) {
            $ymd = $d->format('Y-m-d');
            $isoDow = (int)$d->format('N');

            foreach ($weekly as $w) {
                if ((int)($w['day_of_week'] ?? 0) !== $isoDow) {
                    continue;
                }

                $startM = $this->timeToMinutes((string)($w['start_time'] ?? '00:00'));
                $endM = $this->timeToMinutes((string)($w['end_time'] ?? '00:00'));
                if ($startM >= $endM) {
                    continue;
                }

                $effectiveStart = $startM;
                $intervals = $blockedByDate[$ymd] ?? [];
                $appliedSlotId = 0;
                $statusNote = '';

                $changed = true;
                while ($changed) {
                    $changed = false;
                    foreach ($intervals as $it) {
                        $bs = (int)($it['start'] ?? 0);
                        $be = (int)($it['end'] ?? 0);
                        if ($bs <= $effectiveStart && $be > $effectiveStart) {
                            $effectiveStart = $be;
                            $appliedSlotId = (int)($it['id'] ?? 0);
                            $statusNote = trim((string)($it['note'] ?? ''));
                            $changed = true;
                        }
                    }
                }

                if ($effectiveStart >= $endM) {
                    $rows[] = [
                        'date' => $ymd,
                        'day_name' => $d->format('D'),
                        'base_start_time' => sprintf('%02d:%02d', intdiv($startM, 60), $startM % 60),
                        'start_time' => '--',
                        'end_time' => '--',
                        'status' => 'fully_blocked',
                        'status_note' => $statusNote,
                        'window_id' => (int)($w['id'] ?? 0),
                        'adjusted_slot_id' => $appliedSlotId,
                    ];
                    continue;
                }

                $rows[] = [
                    'date' => $ymd,
                    'day_name' => $d->format('D'),
                    'base_start_time' => sprintf('%02d:%02d', intdiv($startM, 60), $startM % 60),
                    'start_time' => sprintf('%02d:%02d', intdiv($effectiveStart, 60), $effectiveStart % 60),
                    'end_time' => sprintf('%02d:%02d', intdiv($endM, 60), $endM % 60),
                    'status' => ($effectiveStart > $startM) ? 'adjusted' : 'normal',
                    'status_note' => $statusNote,
                    'window_id' => (int)($w['id'] ?? 0),
                    'adjusted_slot_id' => $appliedSlotId,
                ];
            }
        }

        usort($rows, static function (array $a, array $b): int {
            $cmp = strcmp($a['date'], $b['date']);
            if ($cmp !== 0) {
                return $cmp;
            }
            return strcmp($a['start_time'], $b['start_time']);
        });

        return $rows;
    }

    private function isWithinBusinessHours(\DateTimeImmutable $start, \DateTimeImmutable $end): bool
    {
        $startM = ((int)$start->format('H') * 60) + (int)$start->format('i');
        $endM = ((int)$end->format('H') * 60) + (int)$end->format('i');

        return $startM >= 480 && $endM <= 1020;
    }

    public function deleteBlockedSlot(string $id): string
    {
        $user = $this->requireStaffWithHairdresser();
        $this->requireCsrf();

        $hairdresserId = (int)$user['hairdresser_id'];
        $slotId = (int)$id;
        if ($slotId <= 0) {
            http_response_code(404);
            return $this->render('errors/404', ['title' => 'Invalid blocked slot']);
        }

        $this->availability->deleteBlockedSlot($slotId, $hairdresserId);
        $this->flash('success', 'Blocked slot removed.');
        return $this->redirect('/staff/availability');
    }
}

<?php
/** @var array<int, array<string,mixed>> $weekly */
/** @var array<int, array<string,mixed>> $blocked */
/** @var array<int, array<string,mixed>> $blockedThisWeek */
/** @var array<int, array{date:string,day_name:string,start_time:string,end_time:string,status:string,window_id:int}> $weeklyOverview */
/** @var array<int, string> $errors */
/** @var array<string, string> $weeklyForm */
/** @var ?array<string,mixed> $editingWindow */
/** @var ?array<string,mixed> $adjustmentForm */

$errors = $errors ?? [];
$blocked = $blocked ?? [];
$weekly = $weekly ?? [];
$weeklyForm = (isset($weeklyForm) && is_array($weeklyForm)) ? $weeklyForm : [
    'day_of_week' => '',
    'start_time' => '',
    'end_time' => '',
];
$editingWindow = (isset($editingWindow) && is_array($editingWindow)) ? $editingWindow : null;
$weeklyOverview = (isset($weeklyOverview) && is_array($weeklyOverview)) ? $weeklyOverview : [];
$adjustmentForm = (isset($adjustmentForm) && is_array($adjustmentForm)) ? $adjustmentForm : null;

if ($editingWindow === null) {
    if ((string)($weeklyForm['start_time'] ?? '') === '') {
        $weeklyForm['start_time'] = '08:00';
    }
    if ((string)($weeklyForm['end_time'] ?? '') === '') {
        $weeklyForm['end_time'] = '17:00';
    }
}

$weekStart = isset($weekStart) && is_string($weekStart)
    ? $weekStart
    : (new DateTimeImmutable('today'))->format('Y-m-d');
$weekEnd = isset($weekEnd) && is_string($weekEnd)
    ? $weekEnd
    : (new DateTimeImmutable('today'))->modify('+7 days')->format('Y-m-d');

$blockedThisWeek = $blockedThisWeek ?? array_values(array_filter(
    $blocked,
    static fn(array $slot): bool =>
        ((string)($slot['slot_date'] ?? '')) >= $weekStart &&
        ((string)($slot['slot_date'] ?? '')) <= $weekEnd
));

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];

function dayName(int $d): string
{
    return match ($d) {
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
        7 => 'Sun',
        default => 'N/A',
    };
}

/** @return array<int, string> */
function quarterHourTimes(int $startHour, int $endHour): array
{
    $times = [];
    for ($h = $startHour; $h <= $endHour; $h++) {
        foreach ([0, 15, 30, 45] as $m) {
            $times[] = sprintf('%02d:%02d', $h, $m);
        }
    }
    return $times;
}

$weeklyStartTimes = array_values(array_filter(
    quarterHourTimes(8, 16),
    static fn(string $t): bool => $t <= '16:45'
));
$weeklyEndTimes = array_values(array_filter(
    quarterHourTimes(8, 17),
    static fn(string $t): bool => $t >= '08:15' && $t <= '17:00'
));
$blockedStartTimes = $weeklyStartTimes;
$blockedEndTimes = $weeklyEndTimes;
?>

<h1 class="mb-3">Staff - My Availability</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="alert alert-info">
    Changes to blocked slots in the next 7 days are applied immediately to availability.
    Window: <strong><?= htmlspecialchars($weekStart, ENT_QUOTES, 'UTF-8') ?></strong> to
    <strong><?= htmlspecialchars($weekEnd, ENT_QUOTES, 'UTF-8') ?></strong>.
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card p-3">
            <h2 class="h5"><?= $editingWindow !== null ? 'Edit Weekly Availability' : 'Add Weekly Availability' ?></h2>
            <form method="POST" action="<?= $editingWindow !== null
                ? '/staff/availability/' . (int)($editingWindow['id'] ?? 0) . '/update'
                : '/staff/availability' ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-2">
                    <label class="form-label" for="day_of_week">Day</label>
                    <select class="form-select" id="day_of_week" name="day_of_week" required>
                        <?php for ($d = 1; $d <= 5; $d++): ?>
                            <option value="<?= $d ?>" <?= ((string)$d === (string)($weeklyForm['day_of_week'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars(dayName($d), ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endfor; ?>
                        <option value="" disabled style="font-style: italic; color: #999;">— Weekdays Only (Mon-Fri) —</option>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="weekly_start">Start</label>
                    <select class="form-select" id="weekly_start" name="start_time" required>
                        <option value="">-- Select time --</option>
                        <?php foreach ($weeklyStartTimes as $time): ?>
                            <option value="<?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>" <?= ($time === (string)($weeklyForm['start_time'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-2">
                    <label class="form-label" for="weekly_end">End</label>
                    <select class="form-select" id="weekly_end" name="end_time" data-selected="<?= htmlspecialchars((string)($weeklyForm['end_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                        <option value="">-- Select time --</option>
                        <?php foreach ($weeklyEndTimes as $time): ?>
                            <option value="<?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>" <?= ($time === (string)($weeklyForm['end_time'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-primary" type="submit">
                        <?= $editingWindow !== null ? 'Update Weekly Window' : 'Add Weekly Window' ?>
                    </button>
                    <?php if ($editingWindow !== null): ?>
                        <a class="btn btn-outline-secondary" href="/staff/availability">Cancel</a>
                    <?php endif; ?>
                </div>
            </form>

            <hr>

            <h3 class="h6">Weekly Overview (Today + 7 Days)</h3>
            <?php if (empty($weeklyOverview)): ?>
                <div class="text-muted">No availability windows in the selected range.</div>
            <?php else: ?>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-bordered align-middle">
                        <thead><tr><th>Date</th><th>Day</th><th>Start</th><th>End</th><th>Status</th><th>Action</th></tr></thead>
                        <tbody>
                        <?php foreach ($weeklyOverview as $ov): ?>
                            <tr>
                                <td><?= htmlspecialchars((string)($ov['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($ov['day_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($ov['start_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars((string)($ov['end_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <?php $st = (string)($ov['status'] ?? 'normal'); ?>
                                    <?php $statusNote = trim((string)($ov['status_note'] ?? '')); ?>
                                    <?php if ($st === 'adjusted'): ?>
                                        <span class="status-pill status-booked">
                                            <?= htmlspecialchars($statusNote !== '' ? ('Adjusted: ' . $statusNote) : 'Adjusted', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php elseif ($st === 'fully_blocked'): ?>
                                        <span class="status-pill status-cancelled">
                                            <?= htmlspecialchars($statusNote !== '' ? ('Fully blocked: ' . $statusNote) : 'Fully blocked', ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="status-pill status-completed">Normal</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-nowrap">
                                    <?php $wid = (int)($ov['window_id'] ?? 0); ?>
                                    <?php if ($wid > 0): ?>
                                        <a class="btn btn-sm btn-outline-primary" href="/staff/availability?edit_weekly_id=<?= $wid ?>">Edit</a>
                                        <form method="POST" action="/staff/availability/<?= $wid ?>/delete" class="d-inline" onsubmit="return confirm('Delete this weekly window?');">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                            <button class="btn btn-sm btn-outline-danger" type="submit">Delete</button>
                                        </form>
                                        <a class="btn btn-sm btn-outline-secondary" href="/staff/availability?adjust_date=<?= htmlspecialchars((string)($ov['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>&adjust_window_id=<?= $wid ?>">Adjust</a>
                                        <?php if ((int)($ov['adjusted_slot_id'] ?? 0) > 0): ?>
                                            <form method="POST" action="/staff/overview/<?= (int)$ov['adjusted_slot_id'] ?>/clear" class="d-inline" onsubmit="return confirm('Clear this day adjustment?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                                <button class="btn btn-sm btn-outline-warning" type="submit">Clear</button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if ($adjustmentForm !== null): ?>
                <hr>
                <h3 class="h6">Adjust Selected Day</h3>
                <form method="POST" action="/staff/overview/adjust" class="card p-3">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="date" value="<?= htmlspecialchars((string)($adjustmentForm['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="window_id" value="<?= (int)($adjustmentForm['window_id'] ?? 0) ?>">
                    <input type="hidden" name="slot_id" value="<?= (int)($adjustmentForm['slot_id'] ?? 0) ?>">

                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            <input class="form-control" type="text" value="<?= htmlspecialchars((string)($adjustmentForm['date'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" readonly>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="adj_start">Block Start</label>
                            <select class="form-select" id="adj_start" name="start_time" required>
                                <option value="">-- Select time --</option>
                                <?php foreach ($blockedStartTimes as $time): ?>
                                    <option value="<?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>" <?= ($time === (string)($adjustmentForm['start_time'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="adj_end">Block End</label>
                            <select class="form-select" id="adj_end" name="end_time" data-selected="<?= htmlspecialchars((string)($adjustmentForm['end_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" required>
                                <option value="">-- Select time --</option>
                                <?php foreach ($blockedEndTimes as $time): ?>
                                    <option value="<?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?>" <?= ($time === (string)($adjustmentForm['end_time'] ?? '')) ? 'selected' : '' ?>><?= htmlspecialchars($time, ENT_QUOTES, 'UTF-8') ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label" for="adj_note">Reason in status</label>
                            <input class="form-control" id="adj_note" name="note" maxlength="255" value="<?= htmlspecialchars((string)($adjustmentForm['note'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="e.g. Personal appointment">
                        </div>
                    </div>

                    <div class="mt-3 d-flex gap-2">
                        <button class="btn btn-primary" type="submit">Save Adjustment</button>
                        <a class="btn btn-outline-secondary" href="/staff/availability">Cancel</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>

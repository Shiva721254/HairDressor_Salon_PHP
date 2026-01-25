<?php
/** @var array<int,string> $errors */
/** @var array<int, array{id:int,name:string}> $hairdressers */
/** @var array $row */
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
$id = (int)($row['id'] ?? 0);

$oldHairdresserId = (string)($row['hairdresser_id'] ?? '');
$oldDay = (string)($row['day_of_week'] ?? '1');
$oldStart = substr((string)($row['start_time'] ?? '09:00:00'), 0, 5);
$oldEnd   = substr((string)($row['end_time'] ?? '17:00:00'), 0, 5);

function dayLabel(int $day): string
{
    return match ($day) {
        1 => 'Mon',
        2 => 'Tue',
        3 => 'Wed',
        4 => 'Thu',
        5 => 'Fri',
        6 => 'Sat',
        7 => 'Sun',
        default => 'Day ' . $day,
    };
}
?>

<h1 class="mb-3">Edit Availability</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/availability/<?= $id ?>" class="card p-3" style="max-width: 820px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label">Hairdresser</label>
        <select class="form-select" name="hairdresser_id" required>
            <option value="">-- select --</option>
            <?php foreach ($hairdressers as $h): ?>
                <?php $hid = (int)$h['id']; ?>
                <option value="<?= $hid ?>" <?= ((string)$hid === $oldHairdresserId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Day of week</label>
        <select class="form-select" name="day_of_week" required>
            <?php for ($d=1; $d<=7; $d++): ?>
                <option value="<?= $d ?>" <?= ((string)$d === $oldDay) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(dayLabel($d), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Start time</label>
            <input class="form-control" type="time" name="start_time" required
                   value="<?= htmlspecialchars($oldStart, ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">End time</label>
            <input class="form-control" type="time" name="end_time" required
                   value="<?= htmlspecialchars($oldEnd, ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary" type="submit">Save</button>
        <a class="btn btn-outline-secondary" href="/admin/availability">Cancel</a>
    </div>
</form>

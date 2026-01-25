<?php
/** @var array<int,string> $errors */
/** @var array<int, array{id:int,name:string}> $hairdressers */
/** @var array $old */
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');

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

<h1 class="mb-3">Add Availability</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/availability" class="card p-3" style="max-width: 820px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label">Hairdresser</label>
        <select class="form-select" name="hairdresser_id" required>
            <option value="">-- select --</option>
            <?php foreach ($hairdressers as $h): ?>
                <?php $id = (int)$h['id']; ?>
                <option value="<?= $id ?>" <?= ((string)$id === (string)($old['hairdresser_id'] ?? '')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Day of week</label>
        <select class="form-select" name="day_of_week" required>
            <?php for ($d=1; $d<=7; $d++): ?>
                <option value="<?= $d ?>" <?= ((string)$d === (string)($old['day_of_week'] ?? '1')) ? 'selected' : '' ?>>
                    <?= htmlspecialchars(dayLabel($d), ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="row g-3">
        <div class="col-md-6">
            <label class="form-label">Start time</label>
            <input class="form-control" type="time" name="start_time" required
                   value="<?= htmlspecialchars((string)($old['start_time'] ?? '09:00'), ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="col-md-6">
            <label class="form-label">End time</label>
            <input class="form-control" type="time" name="end_time" required
                   value="<?= htmlspecialchars((string)($old['end_time'] ?? '17:00'), ENT_QUOTES, 'UTF-8') ?>">
        </div>
    </div>

    <div class="d-flex gap-2 mt-3">
        <button class="btn btn-primary" type="submit">Create</button>
        <a class="btn btn-outline-secondary" href="/admin/availability">Cancel</a>
    </div>
</form>

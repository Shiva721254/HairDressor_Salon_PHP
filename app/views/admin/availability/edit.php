<?php
/** @var array $row */
/** @var array $hairdressers */
/** @var array $errors */

$errors = $errors ?? [];

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];

$start = substr((string)($row['start_time'] ?? ''), 0, 5);
$end   = substr((string)($row['end_time'] ?? ''), 0, 5);
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

<form method="POST" action="/admin/availability/<?= (int)$row['id'] ?>" class="card p-3" style="max-width: 640px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label">Hairdresser</label>
        <select class="form-select" name="hairdresser_id" required>
            <?php foreach ($hairdressers as $h): ?>
                <option value="<?= (int)$h['id'] ?>" <?= ((int)$row['hairdresser_id'] === (int)$h['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Day of week</label>
        <select class="form-select" name="day_of_week" required>
            <?php for ($d = 1; $d <= 7; $d++): ?>
                <option value="<?= $d ?>" <?= ((int)$row['day_of_week'] === $d) ? 'selected' : '' ?>>
                    <?= ['','Mon','Tue','Wed','Thu','Fri','Sat','Sun'][$d] ?>
                </option>
            <?php endfor; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Start time</label>
        <input class="form-control" type="time" name="start_time" value="<?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">End time</label>
        <input class="form-control" type="time" name="end_time" value="<?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Update</button>
        <a class="btn btn-secondary" href="/admin/availability">Cancel</a>
    </div>
</form>

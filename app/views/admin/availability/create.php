<?php
/** @var array $hairdressers */
/** @var array $errors */
/** @var array $old */

$errors = $errors ?? [];
$old = $old ?? [];

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];

$oldHairdresser = (string)($old['hairdresser_id'] ?? '');
$oldDow = (string)($old['day_of_week'] ?? '');
$oldStart = (string)($old['start_time'] ?? '');
$oldEnd = (string)($old['end_time'] ?? '');
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

<form method="POST" action="/admin/availability" class="card p-3" style="max-width: 640px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label">Hairdresser</label>
        <select class="form-select" name="hairdresser_id" required>
            <option value="">-- select --</option>
            <?php foreach ($hairdressers as $h): ?>
                <?php $hid = (int)$h['id']; ?>
                <option value="<?= $hid ?>" <?= ($oldHairdresser !== '' && (int)$oldHairdresser === $hid) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Day of week</label>
        <select class="form-select" name="day_of_week" required>
            <option value="">-- select --</option>
            <?php
            $days = [1=>'Mon',2=>'Tue',3=>'Wed',4=>'Thu',5=>'Fri',6=>'Sat',7=>'Sun'];
            foreach ($days as $k => $label):
            ?>
                <option value="<?= $k ?>" <?= ($oldDow !== '' && (int)$oldDow === $k) ? 'selected' : '' ?>>
                    <?= $label ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label">Start time</label>
        <input class="form-control" type="time" name="start_time" value="<?= htmlspecialchars($oldStart, ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label">End time</label>
        <input class="form-control" type="time" name="end_time" value="<?= htmlspecialchars($oldEnd, ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save</button>
        <a class="btn btn-secondary" href="/admin/availability">Cancel</a>
    </div>
</form>

<?php

/** @var array<int, array{id:int,name:string}> $hairdressers */
/** @var array<int, array{id:int,name:string,duration_minutes?:int}> $services */
/** @var array<int, string> $errors */

$oldHairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
$oldServiceId     = (int)($_POST['service_id'] ?? 0);
$oldDate          = (string)($_POST['appointment_date'] ?? '');
$oldTime          = (string)($_POST['appointment_time'] ?? '');

// CSRF token
$csrfFieldHtml = '';
if (isset($this) && method_exists($this, 'csrfField')) {
    $csrfFieldHtml = (string)$this->csrfField();
} else {
    $csrfToken = (string)($_SESSION['csrf_token'] ?? '');
    $csrfFieldHtml = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
}

$minDate = date('Y-m-d');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0">Book an appointment</h1>
    <a href="/appointments" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1">Please fix the following:</div>
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/appointments/confirm" class="card shadow-sm" style="max-width: 760px;">
    <div class="card-body p-4">
        <?= $csrfFieldHtml ?>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="hairdresser_id">Hairdresser</label>
                <select class="form-select" id="hairdresser_id" name="hairdresser_id" required>
                    <option value="">-- Select hairdresser --</option>
                    <?php foreach ($hairdressers as $h): ?>
                        <?php $hid = (int)$h['id']; ?>
                        <option value="<?= $hid ?>" <?= ($oldHairdresserId === $hid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="service_id">Service</label>
                <select class="form-select" id="service_id" name="service_id" required>
                    <option value="">-- Select service --</option>
                    <?php foreach ($services as $s): ?>
                        <?php
                        $sid = (int)$s['id'];
                        $name = (string)$s['name'];
                        $dur = isset($s['duration_minutes']) ? (int)$s['duration_minutes'] : null;
                        ?>
                        <option value="<?= $sid ?>" <?= ($oldServiceId === $sid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                            <?= $dur ? ' (' . $dur . ' min)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="appointment_date">Date</label>
                <input
                    class="form-control"
                    id="appointment_date"
                    name="appointment_date"
                    type="date"
                    value="<?= htmlspecialchars($oldDate, ENT_QUOTES, 'UTF-8') ?>"
                    required
                    min="<?= htmlspecialchars($minDate, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-text">Pick a date from today onwards.</div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="appointment_time">Time</label>
                <select class="form-select" name="appointment_time" id="appointment_time" required disabled>
                    <option value="">-- Select hairdresser, service and date first --</option>
                </select>

                <div id="slots-status" class="form-text text-muted mt-1" aria-live="polite"></div>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary" id="btn-continue" disabled>Continue</button>
            <a href="/appointments" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>



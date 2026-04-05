<?php
/** @var string $mode */
/** @var array<int, string> $errors */
/** @var array<int, array{id:int,email:string}> $clients */
/** @var array<int, array{id:int,name:string}> $hairdressers */
/** @var array<int, array{id:int,name:string}> $services */
/** @var array<string, string> $old */
/** @var int|null $appointmentId */

$isEdit = ($mode ?? 'create') === 'edit';
$action = $isEdit
    ? '/admin/appointments/' . (int)($appointmentId ?? 0)
    : '/admin/appointments';
$heading = $isEdit ? 'Edit Appointment (Admin)' : 'Create Appointment (Admin)';

$old = is_array($old ?? null) ? $old : [];
$oldUserId = (int)($old['user_id'] ?? 0);
$oldHairdresserId = (int)($old['hairdresser_id'] ?? 0);
$oldServiceId = (int)($old['service_id'] ?? 0);
$oldDate = (string)($old['appointment_date'] ?? '');
$oldTime = (string)($old['appointment_time'] ?? '');
$oldStatus = (string)($old['status'] ?? 'booked');

$minDate = date('Y-m-d');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0"><?= htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') ?></h1>
    <a href="/appointments?filter=all" class="btn btn-outline-secondary">Back to appointments</a>
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

<form method="POST" action="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>" class="card shadow-sm" style="max-width: 900px;">
    <div class="card-body p-4">
        <?= $this->csrfField() ?>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="user_id">Client</label>
                <select class="form-select" id="user_id" name="user_id" required>
                    <option value="">-- Select client --</option>
                    <?php foreach ($clients as $c): ?>
                        <?php $cid = (int)($c['id'] ?? 0); ?>
                        <option value="<?= $cid ?>" <?= ($oldUserId === $cid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($c['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status" required>
                    <?php foreach (['booked' => 'Booked', 'completed' => 'Completed', 'cancelled' => 'Cancelled'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($oldStatus === $value) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="hairdresser_id">Hairdresser</label>
                <select class="form-select" id="hairdresser_id" name="hairdresser_id" required>
                    <option value="">-- Select hairdresser --</option>
                    <?php foreach ($hairdressers as $h): ?>
                        <?php $hid = (int)($h['id'] ?? 0); ?>
                        <option value="<?= $hid ?>" <?= ($oldHairdresserId === $hid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($h['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="service_id">Service</label>
                <select class="form-select" id="service_id" name="service_id" required>
                    <option value="">-- Select service --</option>
                    <?php foreach ($services as $s): ?>
                        <?php $sid = (int)($s['id'] ?? 0); ?>
                        <option value="<?= $sid ?>" <?= ($oldServiceId === $sid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
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
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="appointment_time">Time</label>
                <input
                    class="form-control"
                    id="appointment_time"
                    name="appointment_time"
                    type="time"
                    value="<?= htmlspecialchars($oldTime, ENT_QUOTES, 'UTF-8') ?>"
                    required>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary">
                <?= $isEdit ? 'Save changes' : 'Create appointment' ?>
            </button>
            <a href="/appointments?filter=all" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

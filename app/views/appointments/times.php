<h1 class="mb-3">Select Date & Time</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="get" action="/appointments/times" class="card p-3 mb-3">
    <input type="hidden" name="hairdresser_id" value="<?= (int)$hairdresser_id ?>">
    <input type="hidden" name="service_id" value="<?= (int)$service_id ?>">

    <div class="mb-3">
        <label for="date" class="form-label">Date</label>
        <input class="form-control" type="date" id="date" name="date" value="<?= htmlspecialchars($date ?? '', ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <button class="btn btn-secondary">Load available times</button>
</form>

<?php if (empty($slots)): ?>
    <div class="alert alert-warning">No available time slots for this date.</div>
<?php else: ?>
    <form method="post" action="/appointments/confirm" class="card p-3">
        <input type="hidden" name="hairdresser_id" value="<?= (int)$hairdresser_id ?>">
        <input type="hidden" name="service_id" value="<?= (int)$service_id ?>">
        <input type="hidden" name="date" value="<?= htmlspecialchars($date ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="mb-2">Available times:</div>

        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($slots as $slot): ?>
                <button class="btn btn-outline-primary" type="submit" name="time" value="<?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>">
                    <?= htmlspecialchars($slot, ENT_QUOTES, 'UTF-8') ?>
                </button>
            <?php endforeach; ?>
        </div>
    </form>
<?php endif; ?>

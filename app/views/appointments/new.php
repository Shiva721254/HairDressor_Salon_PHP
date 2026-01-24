<h1 class="mb-3">Book an Appointment</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="post" action="/appointments/new" class="card p-3">
    <div class="mb-3">
        <label for="hairdresser_id" class="form-label">Hairdresser</label>
        <select class="form-select" id="hairdresser_id" name="hairdresser_id" required>
            <option value="">-- Choose hairdresser --</option>
            <?php foreach ($hairdressers as $h): ?>
                <?php $selected = ((string)($old['hairdresser_id'] ?? '') === (string)$h['id']) ? 'selected' : ''; ?>
                <option value="<?= (int)$h['id'] ?>" <?= $selected ?>>
                    <?= htmlspecialchars($h['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label for="service_id" class="form-label">Service</label>
        <select class="form-select" id="service_id" name="service_id" required>
            <option value="">-- Choose service --</option>
            <?php foreach ($services as $s): ?>
                <?php $selected = ((string)($old['service_id'] ?? '') === (string)$s['id']) ? 'selected' : ''; ?>
                <option value="<?= (int)$s['id'] ?>" <?= $selected ?>>
                    <?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?> (<?= (int)$s['duration_minutes'] ?> min, € <?= number_format((float)$s['price'], 2) ?>)
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <button class="btn btn-primary">Continue</button>
</form>

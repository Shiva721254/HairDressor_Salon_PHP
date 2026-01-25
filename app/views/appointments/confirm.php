<?php
/** @var array $hairdresser */
/** @var array $service */
/** @var string $dateYmd */
/** @var string $timeHi */

$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
?>

<h1 class="mb-3">Confirm your appointment</h1>

<div class="card p-3" style="max-width: 720px;">
    <ul class="list-group list-group-flush mb-3">
        <li class="list-group-item">
            <strong>Hairdresser:</strong>
            <?= htmlspecialchars((string)($hairdresser['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </li>
        <li class="list-group-item">
            <strong>Service:</strong>
            <?= htmlspecialchars((string)($service['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
        </li>
        <li class="list-group-item">
            <strong>Duration:</strong>
            <?= (int)($service['duration_minutes'] ?? 0) ?> minutes
        </li>
        <li class="list-group-item">
            <strong>Price:</strong>
            €<?= number_format((float)($service['price'] ?? 0), 2) ?>
        </li>
        <li class="list-group-item">
            <strong>Date:</strong>
            <?= htmlspecialchars((string)$dateYmd, ENT_QUOTES, 'UTF-8') ?>
        </li>
        <li class="list-group-item">
            <strong>Time:</strong>
            <?= htmlspecialchars((string)$timeHi, ENT_QUOTES, 'UTF-8') ?>
        </li>
    </ul>

   <form method="POST" action="/appointments/finalize">
    <?= $this->csrfField() ?>
    <input type="hidden" name="hairdresser_id" value="<?= (int)$hairdresser['id'] ?>">
    <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>">
    <input type="hidden" name="appointment_date" value="<?= htmlspecialchars((string)$dateYmd, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="appointment_time" value="<?= htmlspecialchars((string)$timeHi, ENT_QUOTES, 'UTF-8') ?>">
    <button type="submit">Confirm booking</button>
</form>

</div>

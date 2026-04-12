<?php
/** @var array $hairdresser */
$hairdresserId = (int)$hairdresser['id'];
?>

<h1 class="mb-3"><?= htmlspecialchars($hairdresser['name'], ENT_QUOTES, 'UTF-8') ?></h1>

<h2 class="h5 mt-4">Weekly availability</h2>

<div id="availability-container" data-hairdresser-id="<?= $hairdresserId ?>">
    <p class="text-muted">Loading availability...</p>
</div>

<a class="btn btn-secondary mt-3" href="/hairdressers">Back to overview</a>
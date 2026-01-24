<?php
/** @var array $hairdresser */
/** @var array $availability */

$days = [
    1 => 'Monday', 2 => 'Tuesday', 3 => 'Wednesday', 4 => 'Thursday',
    5 => 'Friday', 6 => 'Saturday', 7 => 'Sunday',
];
?>

<h1 class="mb-3"><?= htmlspecialchars($hairdresser['name'], ENT_QUOTES, 'UTF-8') ?></h1>

<h2 class="h5 mt-4">Weekly availability</h2>

<?php if (empty($availability)): ?>
    <div class="alert alert-info">No availability set for this hairdresser.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
            <tr>
                <th>Day</th>
                <th>Start</th>
                <th>End</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($availability as $a): ?>
                <tr>
                    <td><?= htmlspecialchars($days[(int)$a['day_of_week']] ?? (string)$a['day_of_week'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$a['start_time'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$a['end_time'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<a class="btn btn-secondary mt-3" href="/hairdressers">Back to overview</a>

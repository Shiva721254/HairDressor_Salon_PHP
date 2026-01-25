<?php
/** @var array $groups */
/** @var ?string $success */
/** @var ?string $error */

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
        default => (string)$day,
    };
}

// Ensure CSRF token exists (for delete forms)
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0">Manage Availability</h1>
    <a class="btn btn-primary" href="/admin/availability/new">Add availability</a>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (empty($groups) || !is_array($groups)): ?>
    <div class="alert alert-info">No availability records found.</div>
<?php else: ?>

    <?php foreach ($groups as $g): ?>
        <?php
        $hairdresserName = (string)($g['hairdresser_name'] ?? 'Unknown');
        $items = $g['items'] ?? [];
        if (!is_array($items)) $items = [];
        $count = count($items);
        ?>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong><?= htmlspecialchars($hairdresserName, ENT_QUOTES, 'UTF-8') ?></strong>
                <span class="text-muted small">
                    <?= $count ?> record<?= $count === 1 ? '' : 's' ?>
                </span>
            </div>

            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0">
                    <thead>
                    <tr>
                        <th style="width: 80px;">ID</th>
                        <th style="width: 120px;">Day</th>
                        <th style="width: 120px;">Start</th>
                        <th style="width: 120px;">End</th>
                        <th class="text-nowrap" style="width: 180px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if ($count === 0): ?>
                        <tr>
                            <td colspan="5" class="text-muted">No records for this hairdresser.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $r): ?>
                            <?php
                            $id = (int)($r['id'] ?? 0);
                            $dow = (int)($r['day_of_week'] ?? 0);

                            // If DB returns HH:MM:SS, keep UI clean as HH:MM
                            $start = substr((string)($r['start_time'] ?? ''), 0, 5);
                            $end   = substr((string)($r['end_time'] ?? ''), 0, 5);
                            ?>
                            <tr>
                                <td><?= $id ?></td>
                                <td><?= htmlspecialchars(dayLabel($dow), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($start, ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($end, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="text-nowrap">
                                    <a class="btn btn-sm btn-outline-primary"
                                       href="/admin/availability/<?= $id ?>/edit">
                                        Edit
                                    </a>

                                    <form method="POST"
                                          action="/admin/availability/<?= $id ?>/delete"
                                          class="d-inline"
                                          onsubmit="return confirm('Delete this availability record?');">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                        <button class="btn btn-sm btn-outline-danger" type="submit">
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endforeach; ?>

<?php endif; ?>

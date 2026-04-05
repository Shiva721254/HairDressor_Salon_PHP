<?php
/** @var array<int, array<string,mixed>> $appointments */
/** @var string $filter */

$filter = isset($filter) && is_string($filter) ? strtolower($filter) : 'upcoming';

function staffTab(string $value, string $label, string $active): string
{
    $href = ($value === 'upcoming')
        ? '/staff/appointments'
        : '/staff/appointments?filter=' . urlencode($value);

    $isActive = ($value === $active) ? ' active' : '';
    $aria = ($value === $active) ? ' aria-current="page"' : '';

    return '<li class="nav-item">' .
        '<a class="nav-link' . $isActive . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $aria . '>' .
        htmlspecialchars($label, ENT_QUOTES, 'UTF-8') .
        '</a>' .
        '</li>';
}

function staffStatusBadge(string $status): string
{
    $status = strtolower($status);

    return match ($status) {
        'booked' => '<span class="status-pill status-booked">Booked</span>',
        'completed' => '<span class="status-pill status-completed">Completed</span>',
        'cancelled' => '<span class="status-pill status-cancelled">Cancelled</span>',
        default => '<span class="status-pill status-unknown">' . htmlspecialchars($status, ENT_QUOTES, 'UTF-8') . '</span>',
    };
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0">Staff - My Appointments</h1>
    <a class="btn btn-outline-secondary" href="/staff/availability">Manage Availability</a>
</div>

<ul class="nav nav-tabs mb-3">
    <?= staffTab('upcoming', 'Upcoming', $filter) ?>
    <?= staffTab('all', 'All', $filter) ?>
    <?= staffTab('completed', 'Completed', $filter) ?>
    <?= staffTab('cancelled', 'Cancelled', $filter) ?>
</ul>

<?php if (empty($appointments)): ?>
    <div class="alert alert-info">No appointments found for your schedule.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
            <tr>
                <th>Date</th>
                <th>Time</th>
                <th>Service</th>
                <th>Client</th>
                <th>Status</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $a): ?>
                <tr>
                    <td><?= htmlspecialchars((string)($a['appointment_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($a['appointment_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($a['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?= htmlspecialchars((string)($a['user_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <span class="text-muted">(<?= htmlspecialchars((string)($a['user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</span>
                    </td>
                    <td><?= staffStatusBadge((string)($a['status'] ?? '')) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

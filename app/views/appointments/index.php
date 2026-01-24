<?php
/** @var array $appointments */
/** @var ?string $success */
/** @var ?string $error */
/** @var string|null $filter */

$filter = isset($filter) && is_string($filter) ? strtolower($filter) : 'upcoming';

function tabLink(string $value, string $label, string $active): string
{
    $href = ($value === 'upcoming')
        ? '/appointments'
        : '/appointments?filter=' . urlencode($value);

    $isActive = ($value === $active) ? ' active' : '';
    $aria = ($value === $active) ? ' aria-current="page"' : '';

    return '<li class="nav-item">' .
        '<a class="nav-link' . $isActive . '" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '"' . $aria . '>' .
        htmlspecialchars($label, ENT_QUOTES, 'UTF-8') .
        '</a>' .
    '</li>';
}

function statusBadge(string $status): string
{
    $status = strtolower($status);

    return match ($status) {
        'booked'    => '<span class="badge bg-primary">Booked</span>',
        'cancelled' => '<span class="badge bg-secondary">Cancelled</span>',
        'completed' => '<span class="badge bg-success">Completed</span>',
        default     => '<span class="badge bg-dark">' .
            htmlspecialchars($status, ENT_QUOTES, 'UTF-8') .
            '</span>',
    };
}
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0">Appointments</h1>
    <a class="btn btn-primary" href="/appointments/new">Book new</a>
</div>

<ul class="nav nav-tabs mb-3">
    <?= tabLink('upcoming', 'Upcoming', $filter) ?>
    <?= tabLink('all', 'All', $filter) ?>
    <?= tabLink('cancelled', 'Cancelled', $filter) ?>
    <?= tabLink('completed', 'Completed', $filter) ?>
</ul>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (empty($appointments)): ?>
    <div class="alert alert-info">No appointments found.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Date</th>
                <th>Time</th>
                <th>Hairdresser</th>
                <th>Service</th>
                <th>User</th>
                <th>Status</th>
                <th class="text-nowrap">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($appointments as $a): ?>
                <?php
                $id = (int)$a['id'];
                $status = (string)($a['status'] ?? '');
                $isBooked = ($status === 'booked');
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= htmlspecialchars((string)$a['appointment_date'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$a['appointment_time'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)$a['hairdresser_name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?= htmlspecialchars((string)$a['service_name'], ENT_QUOTES, 'UTF-8') ?>
                        <span class="text-muted">
                            (<?= (int)$a['service_duration_minutes'] ?> min,
                            €<?= number_format((float)$a['service_price'], 2) ?>)
                        </span>
                    </td>
                    <td>
                        <?= htmlspecialchars((string)$a['user_email'], ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($a['user_role'])): ?>
                            <span class="text-muted">
                                (<?= htmlspecialchars((string)$a['user_role'], ENT_QUOTES, 'UTF-8') ?>)
                            </span>
                        <?php endif; ?>
                    </td>
                    <td><?= statusBadge($status) ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/appointments/<?= $id ?>">View</a>

                        <?php if ($isBooked): ?>
                            <form method="post" action="/appointments/<?= $id ?>/cancel"
                                  class="d-inline"
                                  onsubmit="return confirm('Cancel this appointment?');">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    Cancel
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>
                                —
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

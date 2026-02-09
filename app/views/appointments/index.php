<?php
/** @var array $appointments */
/** @var ?string $success */
/** @var ?string $error */
/** @var string|null $filter */
/** @var array<int, array{id:int,name:string}> $hairdressers */
/** @var array{hairdresser_id:?int,date_from:?string,date_to:?string} $adminFilters */

$filter = isset($filter) && is_string($filter) ? strtolower($filter) : 'upcoming';

$currentUser = $_SESSION['user'] ?? null;
$isLoggedIn = is_array($currentUser);
$isAdmin = $isLoggedIn && (($currentUser['role'] ?? '') === 'admin');
$currentUserId = $isLoggedIn ? (int)($currentUser['id'] ?? 0) : 0;

$adminFilters = isset($adminFilters) && is_array($adminFilters) ? $adminFilters : [
    'hairdresser_id' => null,
    'date_from' => null,
    'date_to' => null,
];

// Ensure CSRF token exists for inline forms (cancel/complete)
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];

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

<?php if ($isAdmin): ?>
    <?php
    $today = (new \DateTimeImmutable('today'))->format('Y-m-d');
    $weekStart = (new \DateTimeImmutable('monday this week'))->format('Y-m-d');
    $weekEnd = (new \DateTimeImmutable('sunday this week'))->format('Y-m-d');

    $filterParam = urlencode((string)$filter);
    $todayLink = '/appointments?filter=' . $filterParam . '&date_from=' . $today . '&date_to=' . $today;
    $weekLink = '/appointments?filter=' . $filterParam . '&date_from=' . $weekStart . '&date_to=' . $weekEnd;
    ?>
    <div class="d-flex flex-wrap gap-2 mb-3">
        <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($todayLink, ENT_QUOTES, 'UTF-8') ?>">Today</a>
        <a class="btn btn-outline-primary btn-sm" href="<?= htmlspecialchars($weekLink, ENT_QUOTES, 'UTF-8') ?>">This week</a>
    </div>

    <form method="GET" action="/appointments" class="card p-3 mb-3">
        <input type="hidden" name="filter" value="<?= htmlspecialchars((string)$filter, ENT_QUOTES, 'UTF-8') ?>">
        <div class="row g-3">
            <div class="col-12 col-md-4">
                <label class="form-label" for="filter_hairdresser">Hairdresser</label>
                <select class="form-select" id="filter_hairdresser" name="hairdresser_id">
                    <option value="">All hairdressers</option>
                    <?php foreach (($hairdressers ?? []) as $h): ?>
                        <?php $hid = (int)($h['id'] ?? 0); ?>
                        <option value="<?= $hid ?>" <?= ($adminFilters['hairdresser_id'] ?? null) === $hid ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)($h['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label" for="filter_date_from">From</label>
                <input
                    class="form-control"
                    id="filter_date_from"
                    name="date_from"
                    type="date"
                    value="<?= htmlspecialchars((string)($adminFilters['date_from'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-12 col-md-3">
                <label class="form-label" for="filter_date_to">To</label>
                <input
                    class="form-control"
                    id="filter_date_to"
                    name="date_to"
                    type="date"
                    value="<?= htmlspecialchars((string)($adminFilters['date_to'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="col-12 col-md-2 d-flex align-items-end gap-2">
                <button class="btn btn-primary w-100" type="submit">Apply</button>
                <a class="btn btn-outline-secondary w-100" href="/appointments?filter=<?= htmlspecialchars((string)$filter, ENT_QUOTES, 'UTF-8') ?>">Reset</a>
            </div>
        </div>
    </form>
<?php endif; ?>

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
                $id = (int)($a['id'] ?? 0);

                $status = strtolower((string)($a['status'] ?? ''));
                $isBooked = ($status === 'booked');

                // Owner logic (requires repository to return user_id in allWithDetails)
                $ownerId = (int)($a['user_id'] ?? 0);
                $canManage = $isLoggedIn && ($isAdmin || $ownerId === $currentUserId);

                // MATCH REPOSITORY KEYS:
                $dur = (int)($a['duration_minutes'] ?? 0);
                $price = (float)($a['price'] ?? 0);
                ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= htmlspecialchars((string)($a['appointment_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($a['appointment_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars((string)($a['hairdresser_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>

                    <td>
                        <?= htmlspecialchars((string)($a['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <span class="text-muted">
                            (<?= $dur ?> min, €<?= number_format($price, 2) ?>)
                        </span>
                    </td>

                    <td>
                        <?= htmlspecialchars((string)($a['user_email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($a['user_role'])): ?>
                            <span class="text-muted">
                                (<?= htmlspecialchars((string)$a['user_role'], ENT_QUOTES, 'UTF-8') ?>)
                            </span>
                        <?php endif; ?>
                    </td>

                    <td><?= statusBadge($status) ?></td>

                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/appointments/<?= $id ?>">View</a>

                        <?php if ($isBooked && $canManage): ?>
                            <?php if ($isAdmin): ?>
                                <form method="POST"
                                      action="/appointments/<?= $id ?>/complete"
                                      class="d-inline"
                                      onsubmit="return confirm('Mark this appointment as completed?');">
                                    <input type="hidden" name="csrf_token"
                                           value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success">
                                        Complete
                                    </button>
                                </form>
                            <?php endif; ?>

                            <form method="POST"
                                  action="/appointments/<?= $id ?>/cancel"
                                  class="d-inline"
                                  onsubmit="return confirm('Cancel this appointment?');">
                                <input type="hidden" name="csrf_token"
                                       value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    Cancel
                                </button>
                            </form>
                        <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled>—</button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
/** @var array $appointment */
/** @var bool|null $isAdmin */

$currentUser = $_SESSION['user'] ?? null;

// Ensure CSRF token exists (for complete/cancel forms)
if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];

// Prefer controller-provided $isAdmin if present; fallback to session-based.
$isAdminResolved = (isset($isAdmin) && is_bool($isAdmin))
    ? $isAdmin
    : (is_array($currentUser) && (($currentUser['role'] ?? '') === 'admin'));

$isOwner = is_array($currentUser)
    && (int)($appointment['user_id'] ?? 0) === (int)($currentUser['id'] ?? 0);

$id = (int)($appointment['id'] ?? 0);

$status = strtolower((string)($appointment['status'] ?? ''));
$isBooked = ($status === 'booked');
?>

<h1 class="mb-3">Appointment Details</h1>

<table class="table table-bordered w-auto">
    <tr>
        <th>ID</th>
        <td><?= $id ?></td>
    </tr>
    <tr>
        <th>Date</th>
        <td><?= htmlspecialchars((string)($appointment['appointment_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Time</th>
        <td><?= htmlspecialchars((string)($appointment['appointment_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Hairdresser</th>
        <td><?= htmlspecialchars((string)($appointment['hairdresser_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Service</th>
        <td>
            <?= htmlspecialchars((string)($appointment['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            <span class="text-muted">
                (<?= (int)($appointment['duration_minutes'] ?? 0) ?> min,
                €<?= number_format((float)($appointment['price'] ?? 0), 2) ?>)
            </span>
        </td>
    </tr>
    <tr>
        <th>User</th>
        <td><?= htmlspecialchars((string)($appointment['user_email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Status</th>
        <td>
            <?php if ($status === 'cancelled'): ?>
                <span class="badge text-bg-secondary">Cancelled</span>
            <?php elseif ($status === 'completed'): ?>
                <span class="badge text-bg-success">Completed</span>
            <?php else: ?>
                <span class="badge text-bg-primary">Booked</span>
            <?php endif; ?>
        </td>
    </tr>
</table>

<div class="d-flex flex-wrap gap-2 mt-3">
    <a href="/appointments" class="btn btn-secondary">Back to overview</a>

    <?php if ($isBooked && $isAdminResolved): ?>
        <form method="POST"
              action="/appointments/<?= $id ?>/complete"
              class="d-inline"
              onsubmit="return confirm('Mark this appointment as completed?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-success">
                Mark as completed
            </button>
        </form>
    <?php endif; ?>

    <?php if ($isBooked && ($isAdminResolved || $isOwner)): ?>
        <form method="POST"
              action="/appointments/<?= $id ?>/cancel"
              class="d-inline"
              onsubmit="return confirm('Cancel this appointment?');">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit" class="btn btn-danger">
                Cancel appointment
            </button>
        </form>
    <?php endif; ?>
</div>

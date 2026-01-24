<?php
/** @var array $appointment */
/** @var array|null $_SESSION['user'] */
?>

<h1 class="mb-3">Appointment Details</h1>

<table class="table table-bordered w-auto">
    <tr>
        <th>ID</th>
        <td><?= (int)$appointment['id'] ?></td>
    </tr>
    <tr>
        <th>Date</th>
        <td><?= htmlspecialchars((string)$appointment['appointment_date'], ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Time</th>
        <td><?= htmlspecialchars((string)$appointment['appointment_time'], ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Hairdresser</th>
        <td><?= htmlspecialchars((string)$appointment['hairdresser_name'], ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Service</th>
        <td>
            <?= htmlspecialchars((string)$appointment['service_name'], ENT_QUOTES, 'UTF-8') ?>
            (<?= (int)$appointment['duration_minutes'] ?> min,
            €<?= htmlspecialchars((string)$appointment['price'], ENT_QUOTES, 'UTF-8') ?>)
        </td>
    </tr>
    <tr>
        <th>User</th>
        <td><?= htmlspecialchars((string)($appointment['user_email'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
    </tr>
    <tr>
        <th>Status</th>
        <td>
            <?php if ($appointment['status'] === 'cancelled'): ?>
                <span class="badge text-bg-secondary">Cancelled</span>
            <?php elseif ($appointment['status'] === 'completed'): ?>
                <span class="badge text-bg-success">Completed</span>
            <?php else: ?>
                <span class="badge text-bg-primary">Booked</span>
            <?php endif; ?>
        </td>
    </tr>
</table>

<div class="d-flex gap-2 mt-3">
    <a href="/appointments" class="btn btn-secondary">Back to overview</a>

    <?php
        $currentUser = $_SESSION['user'] ?? null;
        $isAdmin = is_array($currentUser) && (($currentUser['role'] ?? '') === 'admin');
        $isOwner = is_array($currentUser)
            && (int)($appointment['user_id'] ?? 0) === (int)$currentUser['id'];
    ?>

    <?php if (($appointment['status'] ?? '') === 'booked' && ($isAdmin || $isOwner)): ?>
        <form method="POST"
              action="/appointments/<?= (int)$appointment['id'] ?>/cancel"
              onsubmit="return confirm('Cancel this appointment?');"
              class="d-inline">
            <?= $this->csrfField() ?>
            <button type="submit" class="btn btn-danger">
                Cancel appointment
            </button>
        </form>
    <?php endif; ?>
</div>

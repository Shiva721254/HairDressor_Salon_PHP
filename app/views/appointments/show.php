<?php /** @var array $appointment */ ?>

<h1 class="mb-3">Appointment Details</h1>

<table class="table table-bordered w-auto">
    <tr>
        <th>ID</th>
        <td><?= (int)$appointment['id'] ?></td>
    </tr>
    <tr>
        <th>Date</th>
        <td><?= htmlspecialchars($appointment['appointment_date']) ?></td>
    </tr>
    <tr>
        <th>Time</th>
        <td><?= htmlspecialchars($appointment['appointment_time']) ?></td>
    </tr>
    <tr>
        <th>Hairdresser</th>
        <td><?= htmlspecialchars($appointment['hairdresser_name']) ?></td>
    </tr>
    <tr>
        <th>Service</th>
        <td>
            <?= htmlspecialchars($appointment['service_name']) ?>
            (<?= (int)$appointment['duration_minutes'] ?> min,
            €<?= htmlspecialchars($appointment['price']) ?>)
        </td>
    </tr>
    <tr>
        <th>User</th>
        <td>
            <?= htmlspecialchars($appointment['user_email'] ?? '—') ?>
        </td>
    </tr>
    <tr>
        <th>Status</th>
        <td><?= htmlspecialchars($appointment['status']) ?></td>
    </tr>
</table>

<div class="d-flex gap-2 mt-3">
    <a href="/appointments" class="btn btn-secondary">Back to overview</a>

    <?php if (($appointment['status'] ?? '') !== 'cancelled'): ?>
        <form method="post" action="/appointments/<?= (int)$appointment['id'] ?>/cancel"
              onsubmit="return confirm('Cancel this appointment?');">
            <button type="submit" class="btn btn-danger">Cancel appointment</button>
        </form>
    <?php else: ?>
        <span class="badge text-bg-secondary align-self-center">Cancelled</span>
    <?php endif; ?>
</div>


<a href="/appointments" class="btn btn-secondary mt-3">Back to overview</a>

<?php

/** @var array $appointments */
?>

<h1 class="mb-3">Appointments</h1>

<div class="mb-3">
    <a class="btn btn-primary" href="/appointments/new">Book appointment</a>
</div>

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
                    <th>Actions</th>

                </tr>
            </thead>
            <tbody>
                <?php foreach ($appointments as $a): ?>
                    <tr>
                        <td><?= (int)$a['id'] ?></td>
                        <td><?= htmlspecialchars((string)$a['appointment_date'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$a['appointment_time'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)$a['hairdresser_name'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?= htmlspecialchars((string)$a['service_name'], ENT_QUOTES, 'UTF-8') ?>
                            <span class="text-muted">
                                (€<?= htmlspecialchars((string)$a['service_price'], ENT_QUOTES, 'UTF-8') ?>)
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
                        <td><?= htmlspecialchars((string)$a['status'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <a class="btn btn-sm btn-outline-primary"
                                href="/appointments/<?= (int)$a['id'] ?>">
                                View
                            </a>
                        </td>

                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
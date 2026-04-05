<?php
/** @var array<int, array<string,mixed>> $clients */

function adminClientStatusBadge(string $status): string
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

<h1 class="mb-3">Admin - Client Accounts & Booking History</h1>

<?php if (empty($clients)): ?>
    <div class="alert alert-info">No client accounts found.</div>
<?php else: ?>
    <?php foreach ($clients as $c): ?>
        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <strong>
                    <?= htmlspecialchars((string)($c['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    <span class="text-muted">(<?= htmlspecialchars((string)($c['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</span>
                </strong>
                <span class="small text-muted">
                    total <?= (int)($c['total_appointments'] ?? 0) ?> ·
                    booked <?= (int)($c['booked_count'] ?? 0) ?> ·
                    completed <?= (int)($c['completed_count'] ?? 0) ?> ·
                    cancelled <?= (int)($c['cancelled_count'] ?? 0) ?>
                </span>
            </div>
            <div class="card-body">
                <?php $history = $c['booking_history'] ?? []; ?>
                <?php if (empty($history)): ?>
                    <div class="text-muted">No bookings.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Hairdresser</th>
                                <th>Service</th>
                                <th>Status</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($history as $a): ?>
                                <tr>
                                    <td><?= htmlspecialchars((string)($a['appointment_date'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($a['appointment_time'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($a['hairdresser_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)($a['service_name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= adminClientStatusBadge((string)($a['status'] ?? '')) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

<h1 class="mb-3">Services</h1>

<?php if (empty($services)): ?>
    <div class="alert alert-warning">No services found.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped align-middle">
            <thead>
            <tr>
                <th>Service</th>
                <th class="text-nowrap">Duration</th>
                <th class="text-nowrap">Price</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($services as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$s['duration_minutes'] ?> min</td>
                    <td>€ <?= number_format((float)$s['price'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
/** @var array $services */
/** @var ?string $success */
/** @var ?string $error */
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0">Admin - Services</h1>
    <a class="btn btn-primary" href="/admin/services/new">Add service</a>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (empty($services)): ?>
    <div class="alert alert-info">No services found.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Duration</th>
                <th>Price</th>
                <th class="text-nowrap">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($services as $s): ?>
                <?php $id = (int)$s['id']; ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= (int)$s['duration_minutes'] ?> min</td>
                    <td>€<?= number_format((float)$s['price'], 2) ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/admin/services/<?= $id ?>/edit">Edit</a>

                        <form method="POST" action="/admin/services/<?= $id ?>/delete" class="d-inline"
                              onsubmit="return confirm('Delete this service?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

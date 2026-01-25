<?php
/** @var array $hairdressers */
/** @var ?string $success */
/** @var ?string $error */
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0">Admin - Hairdressers</h1>
    <a class="btn btn-primary" href="/admin/hairdressers/new">Add hairdresser</a>
</div>

<?php if (!empty($success)): ?>
    <div class="alert alert-success"><?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger"><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></div>
<?php endif; ?>

<?php if (empty($hairdressers)): ?>
    <div class="alert alert-info">No hairdressers found.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
            <tr>
                <th style="width: 90px;">ID</th>
                <th>Name</th>
                <th class="text-nowrap" style="width: 200px;">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($hairdressers as $h): ?>
                <?php $id = (int)$h['id']; ?>
                <tr>
                    <td><?= $id ?></td>
                    <td><?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-nowrap">
                        <a class="btn btn-sm btn-outline-primary" href="/admin/hairdressers/<?= $id ?>/edit">Edit</a>

                        <form method="POST" action="/admin/hairdressers/<?= $id ?>/delete" class="d-inline"
                              onsubmit="return confirm('Delete this hairdresser?');">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

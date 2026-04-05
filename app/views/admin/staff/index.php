<?php
/** @var array<int, array> $staff */
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1>Staff Management</h1>
    <a href="/admin/staff/new" class="btn btn-primary">➕ Add Staff Member</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <?= htmlspecialchars($success, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show">
        <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($staff)): ?>
    <div class="alert alert-info">No staff members yet. <a href="/admin/staff/new">Create one</a></div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Hairdresser Profile</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($staff as $s): ?>
                    <tr>
                        <td>
                            <strong><?= htmlspecialchars((string)($s['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>
                        </td>
                        <td>
                            <small><?= htmlspecialchars((string)($s['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <?= $s['hairdresser_id'] ? htmlspecialchars((string)($s['hairdresser_name'] ?? 'N/A'), ENT_QUOTES, 'UTF-8') : '<span class="text-muted">Not assigned</span>' ?>
                        </td>
                        <td>
                            <small class="text-muted"><?= htmlspecialchars((string)($s['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></small>
                        </td>
                        <td>
                            <a href="/admin/staff/<?= (int)$s['id'] ?>/edit" class="btn btn-sm btn-outline-primary">Edit</a>
                            <form method="POST" action="/admin/staff/<?= (int)$s['id'] ?>/delete" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this staff member?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

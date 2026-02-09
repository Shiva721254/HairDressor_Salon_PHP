<h1 class="mb-3">GDPR Requests</h1>

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

<?php if (empty($requests)): ?>
    <div class="alert alert-info">No GDPR requests submitted yet.</div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead>
            <tr>
                <th>ID</th>
                <th>User</th>
                <th>Type</th>
                <th>Status</th>
                <th>Created</th>
                <th class="text-nowrap">Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($requests as $r): ?>
                <?php
                $status = (string)($r['status'] ?? 'pending');
                $isProcessed = ($status === 'processed');
                ?>
                <tr>
                    <td><?= (int)($r['id'] ?? 0) ?></td>
                    <td>
                        <?= htmlspecialchars((string)($r['user_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                        <?php if (!empty($r['user_role'])): ?>
                            <span class="text-muted">(<?= htmlspecialchars((string)$r['user_role'], ENT_QUOTES, 'UTF-8') ?>)</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)($r['type'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($isProcessed): ?>
                            <span class="badge bg-success">Processed</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark">Pending</span>
                        <?php endif; ?>
                    </td>
                    <td><?= htmlspecialchars((string)($r['created_at'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                    <td class="text-nowrap">
                        <?php if ($isProcessed): ?>
                            <span class="text-muted">—</span>
                        <?php else: ?>
                            <form method="POST"
                                  action="/admin/gdpr-requests/<?= (int)($r['id'] ?? 0) ?>/process"
                                  class="d-inline"
                                  onsubmit="return confirm('Mark this request as processed?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <button class="btn btn-sm btn-outline-success" type="submit">Mark processed</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php
/** @var array<int,string> $errors */
/** @var array $old */
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
?>

<h1 class="mb-3">Add Service</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/services" class="card p-3" style="max-width: 720px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" required
               value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Duration (minutes)</label>
        <input class="form-control" name="duration_minutes" type="number" min="1" required
               value="<?= htmlspecialchars((string)($old['duration_minutes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Price (€)</label>
        <input class="form-control" name="price" type="number" min="0" step="0.01" required
               value="<?= htmlspecialchars((string)($old['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Create</button>
        <a class="btn btn-outline-secondary" href="/admin/services">Cancel</a>
    </div>
</form>

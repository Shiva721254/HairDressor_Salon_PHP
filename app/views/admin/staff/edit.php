<?php
/** @var array $staff */
/** @var array<int, array> $hairdressers */
/** @var array<int, string> $errors */
/** @var array $old */
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
$staffId = (int)($staff['id'] ?? 0);
?>

<h1 class="mb-3">Edit Staff Member</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/staff/<?= $staffId ?>" class="card p-3" style="max-width: 720px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" required
               value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Email</label>
        <input class="form-control" name="email" type="email" required
               value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Hairdresser Profile</label>
        <select class="form-select" name="hairdresser_id" required>
            <option value="">-- Select hairdresser --</option>
            <?php foreach ($hairdressers as $h): ?>
                <?php $selected = ((int)($old['hairdresser_id'] ?? 0) === (int)$h['id']) ? 'selected' : ''; ?>
                <option value="<?= (int)$h['id'] ?>" <?= $selected ?>>
                    <?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="alert alert-info">
        <small>💡 To change the password, contact admin or use the profile page after logging in.</small>
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Save Changes</button>
        <a class="btn btn-outline-secondary" href="/admin/staff">Cancel</a>
    </div>
</form>

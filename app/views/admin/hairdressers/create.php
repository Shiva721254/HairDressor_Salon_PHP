<?php
/** @var array<int,string> $errors */
/** @var array{name:string} $old */

$errors = $errors ?? [];
$old = $old ?? ['name' => ''];

if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = (string)$_SESSION['csrf_token'];
?>

<h1 class="mb-3">Add Hairdresser</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/hairdressers" class="card p-3" style="max-width: 640px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label" for="name">Name</label>
        <input
            class="form-control"
            id="name"
            name="name"
            type="text"
            maxlength="100"
            required
            value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Create</button>
        <a class="btn btn-outline-secondary" href="/admin/hairdressers">Cancel</a>
    </div>
</form>

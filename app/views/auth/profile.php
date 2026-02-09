<h1 class="mb-3">My Profile</h1>

<?php if (!empty($success)): ?>
    <div class="alert alert-success">
        <?= htmlspecialchars((string)$success, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/profile" class="card p-3" style="max-width: 520px;">
    <?= $this->csrfField() ?>

    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input
            class="form-control"
            id="email"
            name="email"
            type="email"
            value="<?= htmlspecialchars((string)($old['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            required
            autocomplete="email"
        >
    </div>

    <div class="mb-3">
        <label class="form-label" for="current_password">Current password</label>
        <input
            class="form-control"
            id="current_password"
            name="current_password"
            type="password"
            required
            autocomplete="current-password"
        >
    </div>

    <div class="mb-3">
        <label class="form-label" for="new_password">New password (optional)</label>
        <input
            class="form-control"
            id="new_password"
            name="new_password"
            type="password"
            autocomplete="new-password"
        >
        <div class="form-text">Leave blank if you only want to update your email.</div>
    </div>

    <div class="mb-3">
        <label class="form-label" for="new_password_confirm">Confirm new password</label>
        <input
            class="form-control"
            id="new_password_confirm"
            name="new_password_confirm"
            type="password"
            autocomplete="new-password"
        >
    </div>

    <button class="btn btn-primary" type="submit">Save changes</button>
</form>

<div class="card p-3 mt-4" style="max-width: 520px;">
    <h2 class="h5">GDPR data actions</h2>
    <p class="text-muted mb-3">You can request an export of your data or submit a deletion request.</p>

    <div class="d-flex flex-wrap gap-2">
        <a class="btn btn-outline-primary" href="/profile/export">Download my data</a>

        <form method="POST" action="/profile/delete" class="d-inline" onsubmit="return confirm('Submit a deletion request?');">
            <?= $this->csrfField() ?>
            <button class="btn btn-outline-danger" type="submit">Request account deletion</button>
        </form>
    </div>
</div>

<?php
declare(strict_types=1);

/** @var ?string $error */
/** @var array $errors */
?>

<h1 class="mb-3">Create account</h1>

<?php if (!empty($error)): ?>
    <div class="alert alert-danger">
        <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
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

<form method="POST" action="/register" class="card p-4" style="max-width: 520px;">

    <!-- CSRF protection -->
    <input type="hidden"
           name="csrf_token"
           value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">

    <!-- Email -->
    <div class="mb-3">
        <label class="form-label" for="email">Email</label>
        <input
            class="form-control"
            type="email"
            id="email"
            name="email"
            value="<?= htmlspecialchars((string)($_POST['email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
            required
            autocomplete="email">
    </div>

    <!-- Password -->
    <div class="mb-3">
        <label class="form-label" for="password">Password</label>
        <input
            class="form-control"
            type="password"
            id="password"
            name="password"
            required
            minlength="6"
            autocomplete="new-password">
        <div class="form-text">Use at least 6 characters.</div>
    </div>

    <!-- Confirm Password -->
    <div class="mb-3">
        <label class="form-label" for="password_confirm">Confirm password</label>
        <input
            class="form-control"
            type="password"
            id="password_confirm"
            name="password_confirm"
            required
            minlength="6"
            autocomplete="new-password">
    </div>

    <button class="btn btn-primary w-100" type="submit">
        Create account
    </button>

    <div class="mt-3 small text-center">
        Already have an account?
        <a href="/login">Login</a>
    </div>
</form>

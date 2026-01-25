<h1 class="mb-3">Login</h1>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger">
    <ul class="mb-0">
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="/login" class="card p-3" style="max-width: 520px;">
  <?= $this->csrfField() ?>

  <div class="mb-3">
    <label class="form-label" for="email">Email</label>
    <input
      class="form-control"
      id="email"
      name="email"
      type="email"
      value="<?= htmlspecialchars((string)($oldEmail ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      required
      autocomplete="email"
    >
  </div>

  <div class="mb-3">
    <label class="form-label" for="password">Password</label>
    <input
      class="form-control"
      id="password"
      name="password"
      type="password"
      required
      autocomplete="current-password"
    >
  </div>

  <button class="btn btn-primary" type="submit">Login</button>

  <div class="form-text mt-2">
    Demo accounts: admin@salon.test / Admin123! and client@salon.test / Client123!
  </div>
</form>

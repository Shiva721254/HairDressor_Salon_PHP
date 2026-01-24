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
  <div class="mb-3">
    <label class="form-label">Email</label>
    <input class="form-control" name="email" type="email" required>
  </div>

  <div class="mb-3">
    <label class="form-label">Password</label>
    <input class="form-control" name="password" type="password" required>
  </div>

  <button class="btn btn-primary" type="submit">Login</button>
</form>

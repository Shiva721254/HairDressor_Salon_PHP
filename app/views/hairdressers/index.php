<!doctype html>
<html>
<head><meta charset="utf-8"><title>Hairdressers</title></head>
<body>
<h1>Hairdressers</h1>

<ul>
  <?php foreach ($hairdressers as $h): ?>
    <li>
      <a href="/hairdressers/<?= (int)$h['id'] ?>">
        <?= htmlspecialchars((string)$h['name']) ?>
      </a>
      — <?= htmlspecialchars((string)($h['specialty'] ?? '')) ?>
    </li>
  <?php endforeach; ?>
</ul>

<p><a href="/appointments/create">Book an appointment</a></p>
</body>
</html>

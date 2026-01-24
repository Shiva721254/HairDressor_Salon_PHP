<!doctype html>
<html>
<head><meta charset="utf-8"><title><?= htmlspecialchars((string)$hairdresser['name']) ?></title></head>
<body>
<h1><?= htmlspecialchars((string)$hairdresser['name']) ?></h1>
<p><strong>Specialty:</strong> <?= htmlspecialchars((string)($hairdresser['specialty'] ?? '')) ?></p>
<p><?= nl2br(htmlspecialchars((string)($hairdresser['bio'] ?? ''))) ?></p>

<p><a href="/appointments/create">Book with this hairdresser</a></p>
<p><a href="/hairdressers">Back</a></p>
</body>
</html>

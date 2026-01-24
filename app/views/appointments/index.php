<!doctype html>
<html>
<head><meta charset="utf-8"><title>Appointments</title></head>
<body>
<h1>Appointments</h1>

<p><a href="/appointments/create">Create new</a></p>

<table border="1" cellpadding="6" cellspacing="0">
  <thead>
    <tr>
      <th>ID</th>
      <th>Date</th>
      <th>Time</th>
      <th>Hairdresser</th>
      <th>Service</th>
      <th>User</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach ($appointments as $a): ?>
     <tr>
  <td><?= (int)$a['id'] ?></td>
  <td><?= htmlspecialchars((string)$a['appointment_date']) ?></td>
  <td><?= htmlspecialchars((string)$a['appointment_time']) ?></td>
  <td><?= htmlspecialchars((string)$a['hairdresser_name']) ?></td>
  <td>
    <?= htmlspecialchars((string)$a['service_name']) ?>
    (<?= htmlspecialchars((string)$a['service_price']) ?>)
  </td>
  <td>
    <?= htmlspecialchars((string)$a['user_email']) ?>
    <?php if (!empty($a['user_role'])): ?>
      (<?= htmlspecialchars((string)$a['user_role']) ?>)
    <?php endif; ?>
  </td>
  <td><?= htmlspecialchars((string)$a['status']) ?></td>
</tr>

    <?php endforeach; ?>
  </tbody>
</table>
</body>
</html>

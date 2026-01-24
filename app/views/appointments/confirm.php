<!doctype html>
<html>
<head><meta charset="utf-8"><title>Confirm Appointment</title></head>
<body>
<h1>Confirm your appointment</h1>

<ul>
  <li><strong>Hairdresser:</strong> <?= htmlspecialchars((string)$hairdresser['name']) ?></li>
  <li><strong>Service:</strong> <?= htmlspecialchars((string)$service['name']) ?></li>
  <li><strong>Duration:</strong> <?= (int)$service['duration_minutes'] ?> minutes</li>
  <li><strong>Price:</strong> €<?= htmlspecialchars((string)$service['price']) ?></li>
  <li><strong>Date:</strong> <?= htmlspecialchars((string)$dateYmd) ?></li>
  <li><strong>Time:</strong> <?= htmlspecialchars((string)$timeHi) ?></li>
</ul>


<form method="POST" action="/appointments/finalize">
    <input type="hidden" name="hairdresser_id" value="<?= (int)$hairdresser['id'] ?>">
    <input type="hidden" name="service_id" value="<?= (int)$service['id'] ?>">
    <input type="hidden" name="appointment_date" value="<?= htmlspecialchars($dateYmd) ?>">
    <input type="hidden" name="appointment_time" value="<?= htmlspecialchars($timeHi) ?>">

    <button type="submit">Confirm booking</button>
</form>


<p><a href="/appointments/create">Go back</a></p>
</body>
</html>

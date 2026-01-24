<!doctype html>
<html>
<head><meta charset="utf-8"><title>Booking Success</title></head>
<body>
<h1>Booking successful</h1>

<p>Your appointment has been booked.</p>
<p><strong>Appointment ID:</strong> <?= (int)$newId ?></p>

<p>
  <a href="/appointments">View appointments</a> |
  <a href="/appointments/create">Book another</a>
</p>
</body>
</html>

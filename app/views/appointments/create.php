<!doctype html>
<html>
<head><meta charset="utf-8"><title>Create Appointment</title></head>
<body>
<h1>Book an appointment</h1>

<?php if (!empty($errors)): ?>
  <div style="border:1px solid #c00; padding:10px;">
    <ul>
      <?php foreach ($errors as $e): ?>
        <li><?= htmlspecialchars((string)$e) ?></li>
      <?php endforeach; ?>
    </ul>
  </div>
<?php endif; ?>

<form method="POST" action="/appointments/confirm">

  <label>Hairdresser</label><br>
  <select name="hairdresser_id" required>
    <option value="">-- select --</option>
    <?php foreach ($hairdressers as $h): ?>
      <option value="<?= (int)$h['id'] ?>" <?= ((int)($_POST['hairdresser_id'] ?? 0) === (int)$h['id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars((string)$h['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <br><br>

  <label>Service</label><br>
  <select name="service_id" required>
    <option value="">-- select --</option>
    <?php foreach ($services as $s): ?>
      <option value="<?= (int)$s['id'] ?>" <?= ((int)($_POST['service_id'] ?? 0) === (int)$s['id']) ? 'selected' : '' ?>>
        <?= htmlspecialchars((string)$s['name']) ?>
      </option>
    <?php endforeach; ?>
  </select>
  <br><br>

  <label>Date</label><br>
  <input name="appointment_date" type="date" value="<?= htmlspecialchars((string)($_POST['appointment_date'] ?? '')) ?>" required>
  <br><br>

  <label>Time</label><br>
<select name="appointment_time" id="appointment_time" required>
  <option value="">-- select time --</option>
</select>
  <br><br>

  <button type="submit">Book</button>
</form>

<script>
  const hair = document.querySelector('select[name="hairdresser_id"]');
  const service = document.querySelector('select[name="service_id"]');
  const date = document.querySelector('input[name="appointment_date"]');
  const timeSelect = document.getElementById('appointment_time');

  async function loadSlots() {
    const hid = hair.value;
    const sid = service.value;
    const d = date.value;

    timeSelect.innerHTML = '<option value="">-- select time --</option>';

    if (!hid || !sid || !d) return;

    const res = await fetch(`/appointments/slots?hairdresser_id=${encodeURIComponent(hid)}&service_id=${encodeURIComponent(sid)}&date=${encodeURIComponent(d)}`);
    const data = await res.json();

    if (!data.ok) return;

    for (const t of data.slots) {
      const opt = document.createElement('option');
      opt.value = t;
      opt.textContent = t;
      timeSelect.appendChild(opt);
    }
  }

  hair.addEventListener('change', loadSlots);
  service.addEventListener('change', loadSlots);
  date.addEventListener('change', loadSlots);
</script>





</body>
</html>

<?php
/** @var array<int, array{id:int,name:string}> $hairdressers */
/** @var array<int, array{id:int,name:string}> $services */
/** @var array<int, string> $errors */

$oldHairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
$oldServiceId     = (int)($_POST['service_id'] ?? 0);
$oldDate          = (string)($_POST['appointment_date'] ?? '');
$oldTime          = (string)($_POST['appointment_time'] ?? '');

$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
?>

<h1 class="mb-3">Book an appointment</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/appointments/confirm" class="card p-3" style="max-width: 720px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
 <?= $this->csrfField() ?>
    <div class="mb-3">
        <label class="form-label" for="hairdresser_id">Hairdresser</label>
        <select class="form-select" id="hairdresser_id" name="hairdresser_id" required>
            <option value="">-- select --</option>
            <?php foreach ($hairdressers as $h): ?>
                <?php $hid = (int)$h['id']; ?>
                <option value="<?= $hid ?>" <?= ($oldHairdresserId === $hid) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label" for="service_id">Service</label>
        <select class="form-select" id="service_id" name="service_id" required>
            <option value="">-- select --</option>
            <?php foreach ($services as $s): ?>
                <?php $sid = (int)$s['id']; ?>
                <option value="<?= $sid ?>" <?= ($oldServiceId === $sid) ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string)$s['name'], ENT_QUOTES, 'UTF-8') ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label" for="appointment_date">Date</label>
        <input class="form-control" id="appointment_date" name="appointment_date" type="date"
               value="<?= htmlspecialchars($oldDate, ENT_QUOTES, 'UTF-8') ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label" for="appointment_time">Time</label>
        <select class="form-select" name="appointment_time" id="appointment_time" required>
            <option value="">-- select time --</option>
        </select>
        <div class="form-text">Available times are loaded automatically after selecting hairdresser, service, and date.</div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">Continue</button>
        <a href="/appointments" class="btn btn-outline-secondary">Cancel</a>
    </div>
</form>

<script>
  const hair = document.getElementById('hairdresser_id');
  const service = document.getElementById('service_id');
  const date = document.getElementById('appointment_date');
  const timeSelect = document.getElementById('appointment_time');

  const previouslySelectedTime = <?= json_encode($oldTime) ?>;

  async function loadSlots() {
    const hid = hair.value;
    const sid = service.value;
    const d = date.value;

    timeSelect.innerHTML = '<option value="">-- select time --</option>';
    if (!hid || !sid || !d) return;

    try {
      const res = await fetch(`/appointments/slots?hairdresser_id=${encodeURIComponent(hid)}&service_id=${encodeURIComponent(sid)}&date=${encodeURIComponent(d)}`);
      const data = await res.json();
      if (!data || !data.ok) return;

      for (const t of (data.slots || [])) {
        const opt = document.createElement('option');
        opt.value = t;
        opt.textContent = t;

        if (previouslySelectedTime && t === previouslySelectedTime) {
          opt.selected = true;
        }

        timeSelect.appendChild(opt);
      }
    } catch (e) {
      // silent fail: keep dropdown empty
    }
  }

  hair.addEventListener('change', loadSlots);
  service.addEventListener('change', loadSlots);
  date.addEventListener('change', loadSlots);

  // Load slots on page load if old values exist (validation errors case)
  loadSlots();
</script>

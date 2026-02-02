<?php

/** @var array<int, array{id:int,name:string}> $hairdressers */
/** @var array<int, array{id:int,name:string,duration_minutes?:int}> $services */
/** @var array<int, string> $errors */

$oldHairdresserId = (int)($_POST['hairdresser_id'] ?? 0);
$oldServiceId     = (int)($_POST['service_id'] ?? 0);
$oldDate          = (string)($_POST['appointment_date'] ?? '');
$oldTime          = (string)($_POST['appointment_time'] ?? '');

// Use ONE CSRF approach. Prefer your helper if it exists.
$csrfFieldHtml = '';
if (isset($this) && method_exists($this, 'csrfField')) {
    $csrfFieldHtml = (string)$this->csrfField();
} else {
    $csrfToken = (string)($_SESSION['csrf_token'] ?? '');
    $csrfFieldHtml = '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') . '">';
}

$minDate = date('Y-m-d');
?>

<div class="d-flex align-items-center justify-content-between mb-3">
    <h1 class="mb-0">Book an appointment</h1>
    <a href="/appointments" class="btn btn-outline-secondary">Back</a>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <div class="fw-semibold mb-1">Please fix the following:</div>
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/appointments/confirm" class="card shadow-sm" style="max-width: 760px;">
    <div class="card-body p-4">
        <?= $csrfFieldHtml ?>

        <div class="row g-3">
            <div class="col-12 col-md-6">
                <label class="form-label" for="hairdresser_id">Hairdresser</label>
                <select class="form-select" id="hairdresser_id" name="hairdresser_id" required>
                    <option value="">-- Select hairdresser --</option>
                    <?php foreach ($hairdressers as $h): ?>
                        <?php $hid = (int)$h['id']; ?>
                        <option value="<?= $hid ?>" <?= ($oldHairdresserId === $hid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$h['name'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="service_id">Service</label>
                <select class="form-select" id="service_id" name="service_id" required>
                    <option value="">-- Select service --</option>
                    <?php foreach ($services as $s): ?>
                        <?php
                        $sid = (int)$s['id'];
                        $name = (string)$s['name'];
                        $dur = isset($s['duration_minutes']) ? (int)$s['duration_minutes'] : null;
                        ?>
                        <option value="<?= $sid ?>" <?= ($oldServiceId === $sid) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($name, ENT_QUOTES, 'UTF-8') ?>
                            <?= $dur ? ' (' . $dur . ' min)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="appointment_date">Date</label>
                <input
                    class="form-control"
                    id="appointment_date"
                    name="appointment_date"
                    type="date"
                    value="<?= htmlspecialchars($oldDate, ENT_QUOTES, 'UTF-8') ?>"
                    required
                    min="<?= htmlspecialchars($minDate, ENT_QUOTES, 'UTF-8') ?>">
                <div class="form-text">Pick a date from today onwards.</div>
            </div>

            <div class="col-12 col-md-6">
                <label class="form-label" for="appointment_time">Time</label>
                <select class="form-select" name="appointment_time" id="appointment_time" required disabled>
                    <option value="">-- Select hairdresser, service and date first --</option>
                </select>

                <div id="slots-status" class="form-text text-muted mt-1" aria-live="polite"></div>
            </div>
        </div>

        <hr class="my-4">

        <div class="d-flex flex-wrap gap-2">
            <button type="submit" class="btn btn-primary" id="btn-continue" disabled>Continue</button>
            <a href="/appointments" class="btn btn-outline-secondary">Cancel</a>
        </div>
    </div>
</form>

<script>
(() => {
  const hairdresserEl = document.getElementById('hairdresser_id');
  const serviceEl     = document.getElementById('service_id');
  const dateEl        = document.getElementById('appointment_date');
  const timeEl        = document.getElementById('appointment_time');
  const statusEl      = document.getElementById('slots-status');
  const continueBtn   = document.getElementById('btn-continue');

  if (!hairdresserEl || !serviceEl || !dateEl || !timeEl) return;

  function setDisabled(message) {
    timeEl.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = message;
    timeEl.appendChild(opt);
    timeEl.disabled = true;
    if (continueBtn) continueBtn.disabled = true;
    if (statusEl) statusEl.textContent = '';
  }

  function fillSlots(slots) {
    timeEl.innerHTML = '';

    if (!Array.isArray(slots) || slots.length === 0) {
      setDisabled('No available times for this date');
      if (statusEl) statusEl.textContent = 'No available times found.';
      return;
    }

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = '-- Select time --';
    timeEl.appendChild(placeholder);

    for (const t of slots) {
      const opt = document.createElement('option');
      opt.value = t;
      opt.textContent = t;
      timeEl.appendChild(opt);
    }

    timeEl.disabled = false;
    if (statusEl) statusEl.textContent = `Found ${slots.length} available time slots.`;
  }

  async function loadSlots() {
    const hairdresserId = hairdresserEl.value;
    const serviceId = serviceEl.value;
    const date = dateEl.value; // YYYY-MM-DD

    if (!hairdresserId || !serviceId || !date) {
      setDisabled('-- Select hairdresser, service and date first --');
      return;
    }

    setDisabled('Loading available times...');
    if (statusEl) statusEl.textContent = 'Loading...';

    const url = `/api/slots?hairdresser_id=${encodeURIComponent(hairdresserId)}&service_id=${encodeURIComponent(serviceId)}&date=${encodeURIComponent(date)}`;

    try {
      const res = await fetch(url, { headers: { 'Accept': 'application/json' } });

      if (!res.ok) {
        setDisabled(`Error loading times (${res.status})`);
        if (statusEl) statusEl.textContent = `Failed to load slots (${res.status}).`;
        return;
      }

      const data = await res.json();
      const slots = (data && Array.isArray(data.slots)) ? data.slots : [];

      fillSlots(slots);
    } catch (e) {
      setDisabled('Network error loading times');
      if (statusEl) statusEl.textContent = 'Network error.';
    }
  }

  function onTimeChange() {
    const hasTime = !!timeEl.value;
    if (continueBtn) continueBtn.disabled = !hasTime;
  }

  hairdresserEl.addEventListener('change', loadSlots);
  serviceEl.addEventListener('change', loadSlots);
  dateEl.addEventListener('change', loadSlots);
  timeEl.addEventListener('change', onTimeChange);

  // initial state
  setDisabled('-- Select hairdresser, service and date first --');
})();
</script>


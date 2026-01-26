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
    (function() {
        const hairEl = document.getElementById('hairdresser_id');
        const serviceEl = document.getElementById('service_id');
        const dateEl = document.getElementById('appointment_date');
        const timeEl = document.getElementById('appointment_time');
        const statusEl = document.getElementById('slots-status');
        const continueBtn = document.getElementById('btn-continue');

        const previouslySelectedTime = <?= json_encode($oldTime, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?>;

        let lastRequestKey = null;

        let workingDays = null; // e.g. [1,2,3,4,5]

        async function loadWorkingDays() {
            workingDays = null;

            const hid = hairEl.value;
            if (!hid) return;

            try {
                const res = await fetch(`/api/hairdressers/${encodeURIComponent(hid)}/availability`, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (!res.ok) return;

                const data = await res.json();
                if (data && data.ok === true && Array.isArray(data.working_days)) {
                    workingDays = data.working_days;
                }
            } catch (e) {
                // ignore (workingDays stays null)
            }
        }

        function isDateAllowed(dateStr) {
            if (!dateStr) return true;
            if (!workingDays || workingDays.length === 0) return true; // unknown -> do not block

            const d = new Date(dateStr + 'T00:00:00');
            const dow = d.getDay(); // 0..6 (Sun..Sat)
            return workingDays.includes(dow);
        }

        function humanWorkingDays(days) {
            const map = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
            return (days || []).map(d => map[d] ?? String(d)).join(', ');
        }

        function enforceWorkingDaySelection() {
            const value = dateEl.value;
            if (!value) return;

            // Only enforce if workingDays is known
            if (!Array.isArray(workingDays) || workingDays.length === 0) return;

            const d = new Date(value + 'T00:00:00');
            const dow = d.getDay(); // 0..6

            if (!workingDays.includes(dow)) {
                // Reset invalid date selection
                dateEl.value = '';
                setTimeDisabled('No times available');
                setStatus(`This hairdresser works on: ${humanWorkingDays(workingDays)}.`);
            }
        }





        function setStatus(msg) {
            statusEl.textContent = msg || '';
        }

        function setTimeDisabled(message) {
            timeEl.innerHTML = '';
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = message;
            timeEl.appendChild(opt);
            timeEl.disabled = true;
            continueBtn.disabled = true;
        }

        function setTimeOptions(slots) {
            timeEl.innerHTML = '';

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = '-- Select a time --';
            timeEl.appendChild(placeholder);

            for (const t of slots) {
                const opt = document.createElement('option');
                opt.value = t;
                opt.textContent = t;

                if (previouslySelectedTime && t === previouslySelectedTime) {
                    opt.selected = true;
                }

                timeEl.appendChild(opt);
            }

            timeEl.disabled = false;

            // Enable Continue only if a valid time is selected
            continueBtn.disabled = !timeEl.value;
        }

        function canQuerySlots() {
            return Boolean(hairEl.value && serviceEl.value && dateEl.value);
        }

        async function loadSlots() {
            if (!canQuerySlots()) {
                setTimeDisabled('-- Select hairdresser, service and date first --');
                setStatus('');
                return;
            }
            // Block dates outside working days
            // Block dates outside working days (only if we actually loaded workingDays)
            if (dateEl.value && Array.isArray(workingDays) && workingDays.length > 0 && !isDateAllowed(dateEl.value)) {
                setTimeDisabled('No times available');
                setStatus(`This hairdresser works on: ${humanWorkingDays(workingDays)}.`);
                return;
            }



            const hid = hairEl.value;
            const sid = serviceEl.value;
            const d = dateEl.value;

            const requestKey = `${hid}|${sid}|${d}`;
            lastRequestKey = requestKey;

            setTimeDisabled('Loading available times...');
            setStatus('Loading available time slots...');

            const params = new URLSearchParams({
                hairdresser_id: hid,
                service_id: sid,
                date: d
            });

            try {
                // Preferred API route:
                const url = `/api/slots?${params.toString()}`;

                // If you did NOT add /api/slots route, use this instead:
                // const url = `/appointments/slots?${params.toString()}`;

                const res = await fetch(url, {
                    headers: {
                        'Accept': 'application/json'
                    }
                });

                if (lastRequestKey !== requestKey) return;

                if (!res.ok) {
                    setTimeDisabled('No times available');
                    setStatus(`Could not load slots (HTTP ${res.status}).`);
                    return;
                }

                const data = await res.json();

                if (!data || data.ok !== true || !Array.isArray(data.slots)) {
                    setTimeDisabled('No times available');
                    setStatus('No valid slot data returned.');
                    return;
                }

                if (data.slots.length === 0) {
                    setTimeDisabled('No times available');
                    setStatus('No available slots for this selection.');
                    return;
                }

                setTimeOptions(data.slots);
                setStatus(`${data.slots.length} slot(s) available.`);
            } catch (e) {
                if (lastRequestKey !== requestKey) return;
                setTimeDisabled('No times available');
                setStatus('Network error while loading slots.');
            }
        }

        hairEl.addEventListener('change', function() {
            // When hairdresser changes, refresh working days first, then load slots
            loadWorkingDays().then(loadSlots);
        });

        serviceEl.addEventListener('change', loadSlots);
        dateEl.addEventListener('change', function() {
            enforceWorkingDaySelection();
            loadSlots();
        });



        timeEl.addEventListener('change', function() {
            continueBtn.disabled = !timeEl.value;
        });

        // Initial load (supports “old values” after validation errors)
        loadWorkingDays().then(loadSlots);

    })();
</script>
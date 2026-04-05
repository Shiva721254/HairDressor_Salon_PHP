(() => {
  const hairdresserEl = document.querySelector('select[name="hairdresser_id"]');
  const serviceEl     = document.querySelector('select[name="service_id"]');
  const dateEl        = document.querySelector('input[name="date"]');
  const timeEl        = document.querySelector('select[name="time"]');

  if (!hairdresserEl || !serviceEl || !dateEl || !timeEl) return;

  function setDisabled(text) {
    timeEl.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = text;
    timeEl.appendChild(opt);
    timeEl.disabled = true;
  }

  async function loadSlots() {
    const hairdresserId = hairdresserEl.value;
    const serviceId = serviceEl.value;
    const date = dateEl.value;

    if (!hairdresserId || !serviceId || !date) {
      setDisabled('-- Select hairdresser, service and date first --');
      return;
    }

    setDisabled('Loading available times...');

    try {
      const response = await fetch(
        `/api/slots?hairdresser_id=${encodeURIComponent(hairdresserId)}&service_id=${encodeURIComponent(serviceId)}&date=${encodeURIComponent(date)}`,
        { headers: { 'Accept': 'application/json' } }
      );

      if (!response.ok) {
        setDisabled('Failed to load times');
        return;
      }

      const data = await response.json();

      // ✅ IMPORTANT: your API returns { slots: [...] }
      const slots = data.slots;

      timeEl.innerHTML = '';

      if (!Array.isArray(slots) || slots.length === 0) {
        setDisabled('No available times for this date');
        return;
      }

      const placeholder = document.createElement('option');
      placeholder.value = '';
      placeholder.textContent = '-- Select time --';
      timeEl.appendChild(placeholder);

      for (const time of slots) {
        const opt = document.createElement('option');
        opt.value = time;
        opt.textContent = time;
        timeEl.appendChild(opt);
      }

      timeEl.disabled = false;

    } catch (err) {
      setDisabled('Network error loading times');
    }
  }

  hairdresserEl.addEventListener('change', loadSlots);
  serviceEl.addEventListener('change', loadSlots);
  dateEl.addEventListener('change', loadSlots);

  setDisabled('-- Select hairdresser, service and date first --');
})();

(() => {
  const startEl = document.querySelector('#weekly_start');
  const endEl = document.querySelector('#weekly_end');

  if (!startEl || !endEl) return;

  const allEndOptions = Array.from(endEl.querySelectorAll('option'))
    .map((opt) => ({ value: opt.value, label: opt.textContent || '' }))
    .filter((opt) => opt.value !== '');

  function toMinutes(hhmm) {
    const parts = hhmm.split(':');
    if (parts.length !== 2) return -1;
    const h = Number(parts[0]);
    const m = Number(parts[1]);
    if (!Number.isInteger(h) || !Number.isInteger(m)) return -1;
    return (h * 60) + m;
  }

  function rebuildEndOptions() {
    const selectedStart = startEl.value;
    const selectedEnd = endEl.value || endEl.dataset.selected || '';
    const startMins = toMinutes(selectedStart);

    endEl.innerHTML = '';

    const placeholder = document.createElement('option');
    placeholder.value = '';
    placeholder.textContent = selectedStart
      ? '-- Select end time --'
      : '-- Select start time first --';
    endEl.appendChild(placeholder);

    if (!selectedStart || startMins < 0) {
      endEl.disabled = true;
      return;
    }

    const allowed = allEndOptions.filter((opt) => toMinutes(opt.value) > startMins);
    for (const opt of allowed) {
      const el = document.createElement('option');
      el.value = opt.value;
      el.textContent = opt.label;
      if (opt.value === selectedEnd) {
        el.selected = true;
      }
      endEl.appendChild(el);
    }

    endEl.disabled = (allowed.length === 0);
    if (allowed.length === 0) {
      placeholder.textContent = '-- No end times available --';
    }
  }

  startEl.addEventListener('change', () => {
    endEl.dataset.selected = '';
    rebuildEndOptions();
  });

  rebuildEndOptions();
})();

/* ========================================
   CUSTOM DROPDOWN FOR DURATION
   ======================================== */

(() => {
  const durationBtn = document.getElementById('durationBtn');
  const durationMenu = document.getElementById('durationMenu');
  const durationLabel = document.getElementById('durationLabel');
  const durationInput = document.getElementById('duration_minutes');
  
  if (!durationBtn || !durationMenu || !durationInput) return;

  // Initialize label with current value
  function updateLabel() {
    const items = durationMenu.querySelectorAll('.custom-dropdown-item[data-value]');
    items.forEach(item => item.classList.remove('active'));
    
    const currentValue = durationInput.value;
    if (currentValue) {
      const activeItem = durationMenu.querySelector(`.custom-dropdown-item[data-value="${currentValue}"]`);
      if (activeItem) {
        durationLabel.textContent = activeItem.textContent;
        activeItem.classList.add('active');
      }
    } else {
      durationLabel.textContent = 'Select duration...';
    }
  }

  // Toggle menu on button click
  durationBtn.addEventListener('click', (e) => {
    e.preventDefault();
    durationBtn.classList.toggle('active');
    durationMenu.classList.toggle('show');
  });

  // Select item
  durationMenu.querySelectorAll('.custom-dropdown-item[data-value]').forEach(item => {
    item.addEventListener('click', () => {
      const value = item.dataset.value;
      durationInput.value = value;
      durationBtn.classList.remove('active');
      durationMenu.classList.remove('show');
      updateLabel();
    });
  });

  // Close on outside click
  document.addEventListener('click', (e) => {
    if (!durationBtn.contains(e.target) && !durationMenu.contains(e.target)) {
      durationBtn.classList.remove('active');
      durationMenu.classList.remove('show');
    }
  });

  // Initial label update
  updateLabel();
})();

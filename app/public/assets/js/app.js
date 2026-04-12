/* ============================================================
 * Booking — Slot Loader
 * Used on: /appointments/new
 * ============================================================ */
(() => {
  const hairdresserEl = document.getElementById('hairdresser_id');
  const serviceEl     = document.getElementById('service_id');
  const dateEl        = document.getElementById('appointment_date');
  const timeEl        = document.getElementById('appointment_time');
  const statusEl      = document.getElementById('slots-status');
  const continueBtn   = document.getElementById('btn-continue');

  if (!hairdresserEl || !serviceEl || !dateEl || !timeEl) return;

  function clearTimeSelectAndShowDisabledMessage(message) {
    timeEl.innerHTML = '';
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = message;
    timeEl.appendChild(opt);
    timeEl.disabled = true;
    if (continueBtn) continueBtn.disabled = true;
    if (statusEl) statusEl.textContent = '';
  }

  function appendSingleTimeOptionToTimeSelect(time) {
    const opt = document.createElement('option');
    opt.value = time;
    opt.textContent = time;
    timeEl.appendChild(opt);
  }

  function populateTimeSelectWithAvailableSlots(slots) {
    timeEl.innerHTML = '';
    const ph = document.createElement('option');
    ph.value = '';
    ph.textContent = '-- Select time --';
    timeEl.appendChild(ph);
    slots.forEach(appendSingleTimeOptionToTimeSelect);
    timeEl.disabled = false;
    if (statusEl) statusEl.textContent = `Found ${slots.length} available time slots.`;
  }

  function applyApiSlotsToTimeSelectOrShowEmpty(slots) {
    if (!Array.isArray(slots) || slots.length === 0) {
      clearTimeSelectAndShowDisabledMessage('No available times for this date');
      if (statusEl) statusEl.textContent = 'No available times found.';
      return;
    }
    populateTimeSelectWithAvailableSlots(slots);
  }

  function getBookingFormValues() {
    return {
      hairdresserId: hairdresserEl.value,
      serviceId: serviceEl.value,
      date: dateEl.value,
    };
  }

  function buildSlotsApiUrl(hairdresserId, serviceId, date) {
    return `/api/slots?hairdresser_id=${encodeURIComponent(hairdresserId)}&service_id=${encodeURIComponent(serviceId)}&date=${encodeURIComponent(date)}`;
  }

  async function fetchSlotsFromApiAndFillTimeSelect(url) {
    const res = await fetch(url, { headers: { 'Accept': 'application/json' } });
    if (!res.ok) {
      clearTimeSelectAndShowDisabledMessage(`Error loading times (${res.status})`);
      if (statusEl) statusEl.textContent = `Failed to load slots (${res.status}).`;
      return;
    }
    const data = await res.json();
    applyApiSlotsToTimeSelectOrShowEmpty((data && Array.isArray(data.slots)) ? data.slots : []);
  }

  async function fetchSlotsOrShowNetworkError(url) {
    try {
      await fetchSlotsFromApiAndFillTimeSelect(url);
    } catch {
      clearTimeSelectAndShowDisabledMessage('Network error loading times');
      if (statusEl) statusEl.textContent = 'Network error.';
    }
  }

  async function fetchAndPopulateAvailableTimeSlots() {
    const { hairdresserId, serviceId, date } = getBookingFormValues();
    if (!hairdresserId || !serviceId || !date) {
      clearTimeSelectAndShowDisabledMessage('-- Select hairdresser, service and date first --');
      return;
    }
    clearTimeSelectAndShowDisabledMessage('Loading available times...');
    if (statusEl) statusEl.textContent = 'Loading...';
    await fetchSlotsOrShowNetworkError(buildSlotsApiUrl(hairdresserId, serviceId, date));
  }

  function disableContinueButtonWhenNoTimeSelected() {
    if (continueBtn) continueBtn.disabled = !timeEl.value;
  }

  hairdresserEl.addEventListener('change', fetchAndPopulateAvailableTimeSlots);
  serviceEl.addEventListener('change', fetchAndPopulateAvailableTimeSlots);
  dateEl.addEventListener('change', fetchAndPopulateAvailableTimeSlots);
  timeEl.addEventListener('change', disableContinueButtonWhenNoTimeSelected);

  clearTimeSelectAndShowDisabledMessage('-- Select hairdresser, service and date first --');
})();

/* ============================================================
 * Time Range Picker — filter end times to be after start time
 * Used on: /staff/availability (weekly_start/weekly_end, adj_start/adj_end)
 * ============================================================ */
(() => {
  function convertHhmmStringToMinutes(hhmm) {
    const parts = String(hhmm || '').split(':');
    if (parts.length !== 2) return -1;
    const h = Number(parts[0]);
    const m = Number(parts[1]);
    if (!Number.isInteger(h) || !Number.isInteger(m)) return -1;
    return h * 60 + m;
  }

  function createPlaceholderOptionForEndSelect(startValue) {
    const opt = document.createElement('option');
    opt.value = '';
    opt.textContent = startValue ? '-- Select end time --' : '-- Select start time first --';
    return opt;
  }

  function cloneSourceOptionAndMarkIfSelected(sourceOpt, selectedValue) {
    const next = document.createElement('option');
    next.value = sourceOpt.value;
    next.textContent = sourceOpt.textContent;
    if (sourceOpt.value === selectedValue) next.selected = true;
    return next;
  }

  function appendEndTimeOptionsLaterThanStartMinutes(endEl, allOptions, startMins, selected) {
    let count = 0;
    for (const opt of allOptions) {
      if (!opt.value || convertHhmmStringToMinutes(opt.value) <= startMins) continue;
      endEl.appendChild(cloneSourceOptionAndMarkIfSelected(opt, selected));
      count++;
    }
    return count;
  }

  function clearEndSelectAndInsertPlaceholder(startEl, endEl) {
    endEl.innerHTML = '';
    const placeholder = createPlaceholderOptionForEndSelect(startEl.value);
    endEl.appendChild(placeholder);
    return placeholder;
  }

  function rebuildValidEndTimeOptionsFromStartValue(startEl, endEl, allOptions) {
    const start     = startEl.value;
    const selected  = endEl.value || endEl.dataset.selected || '';
    const startMins = convertHhmmStringToMinutes(start);
    const placeholder = clearEndSelectAndInsertPlaceholder(startEl, endEl);
    if (!start || startMins < 0) { endEl.disabled = true; return; }
    const count = appendEndTimeOptionsLaterThanStartMinutes(endEl, allOptions, startMins, selected);
    endEl.disabled = (count === 0);
    if (count === 0) placeholder.textContent = '-- No end times available --';
  }

  function bindStartAndEndTimeSelectsToFilterEachOther(startId, endId) {
    const startEl    = document.getElementById(startId);
    const endEl      = document.getElementById(endId);
    if (!startEl || !endEl) return;
    const allOptions = Array.from(endEl.querySelectorAll('option'));
    const rebuild    = () => rebuildValidEndTimeOptionsFromStartValue(startEl, endEl, allOptions);
    startEl.addEventListener('change', () => { endEl.dataset.selected = ''; rebuild(); });
    rebuild();
  }

  bindStartAndEndTimeSelectsToFilterEachOther('weekly_start', 'weekly_end');
  bindStartAndEndTimeSelectsToFilterEachOther('adj_start', 'adj_end');
})();

/* ============================================================
 * Duration Dropdown — custom dropdown for service duration
 * Used on: /admin/services (create / edit)
 * ============================================================ */
(() => {
  const durationBtn   = document.getElementById('durationBtn');
  const durationMenu  = document.getElementById('durationMenu');
  const durationLabel = document.getElementById('durationLabel');
  const durationInput = document.getElementById('duration_minutes');

  if (!durationBtn || !durationMenu || !durationInput) return;

  function removeActiveStateFromAllDurationMenuItems() {
    durationMenu.querySelectorAll('.custom-dropdown-item[data-value]')
      .forEach(item => item.classList.remove('active'));
  }

  function activateDurationMenuItemMatchingValueAndUpdateLabel(value) {
    const item = durationMenu.querySelector(`.custom-dropdown-item[data-value="${value}"]`);
    if (!item) return;
    if (durationLabel) durationLabel.textContent = item.textContent;
    item.classList.add('active');
  }

  function syncDurationLabelAndActiveItemWithCurrentInputValue() {
    removeActiveStateFromAllDurationMenuItems();
    if (durationInput.value) {
      activateDurationMenuItemMatchingValueAndUpdateLabel(durationInput.value);
    } else if (durationLabel) {
      durationLabel.textContent = 'Select duration...';
    }
  }

  function toggleDurationMenuOpenOrClosed(e) {
    e.preventDefault();
    durationBtn.classList.toggle('active');
    durationMenu.classList.toggle('show');
  }

  function selectClickedDurationItemAndCloseMenu(item) {
    durationInput.value = item.dataset.value;
    durationBtn.classList.remove('active');
    durationMenu.classList.remove('show');
    syncDurationLabelAndActiveItemWithCurrentInputValue();
  }

  function closeDurationMenuWhenUserClicksOutside(e) {
    if (!durationBtn.contains(e.target) && !durationMenu.contains(e.target)) {
      durationBtn.classList.remove('active');
      durationMenu.classList.remove('show');
    }
  }

  durationBtn.addEventListener('click', toggleDurationMenuOpenOrClosed);
  durationMenu.querySelectorAll('.custom-dropdown-item[data-value]')
    .forEach(item => item.addEventListener('click', () => selectClickedDurationItemAndCloseMenu(item)));
  document.addEventListener('click', closeDurationMenuWhenUserClicksOutside);

  syncDurationLabelAndActiveItemWithCurrentInputValue();
})();

/* ============================================================
 * Hairdresser Availability — fetch and render working days
 * Used on: /hairdressers/:id
 * Reads hairdresser ID from data-hairdresser-id on #availability-container
 * ============================================================ */
(() => {
  const container = document.getElementById('availability-container');
  if (!container) return;

  const hairdresserId = Number(container.dataset.hairdresserId);
  if (!hairdresserId) return;

  const DAY_NAMES = {
    1: 'Monday', 2: 'Tuesday', 3: 'Wednesday', 4: 'Thursday',
    5: 'Friday', 6: 'Saturday', 7: 'Sunday'
  };

  function buildTableRowForEachWorkingDay(workingDays) {
    return workingDays.map(d => `<tr><td>${DAY_NAMES[d] ?? 'Unknown'}</td></tr>`).join('');
  }

  function buildWorkingDaysTableHtml(workingDays) {
    return `<div class="table-responsive">
      <table class="table table-striped table-bordered align-middle">
        <thead><tr><th>Working Day</th></tr></thead>
        <tbody>${buildTableRowForEachWorkingDay(workingDays)}</tbody>
      </table>
    </div>`;
  }

  function renderWorkingDaysTableOrNoAvailabilityNotice(data) {
    const workingDays = data.working_days ?? [];
    if (workingDays.length === 0) {
      container.innerHTML = '<div class="alert alert-info">No availability set for this hairdresser.</div>';
      return;
    }
    container.innerHTML = buildWorkingDaysTableHtml(workingDays);
  }

  function renderAvailabilityFetchFailedError() {
    container.innerHTML = '<div class="alert alert-danger">Could not load availability.</div>';
  }

  fetch(`/api/hairdressers/${hairdresserId}/availability`, { headers: { 'Accept': 'application/json' } })
    .then(res => { if (!res.ok) throw new Error('Failed'); return res.json(); })
    .then(renderWorkingDaysTableOrNoAvailabilityNotice)
    .catch(renderAvailabilityFetchFailedError);
})();

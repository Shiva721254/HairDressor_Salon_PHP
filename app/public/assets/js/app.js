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

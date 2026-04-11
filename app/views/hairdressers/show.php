<?php
/** @var array $hairdresser */
$hairdresserId = (int)$hairdresser['id'];
?>

<h1 class="mb-3"><?= htmlspecialchars($hairdresser['name'], ENT_QUOTES, 'UTF-8') ?></h1>

<h2 class="h5 mt-4">Weekly availability</h2>

<div id="availability-container">
    <p class="text-muted">Loading availability...</p>
</div>

<a class="btn btn-secondary mt-3" href="/hairdressers">Back to overview</a>

<script>
(() => {
    const days = {
        1: 'Monday', 2: 'Tuesday', 3: 'Wednesday',
        4: 'Thursday', 5: 'Friday', 6: 'Saturday', 7: 'Sunday'
    };

    const container = document.getElementById('availability-container');
    const hairdresserId = <?= $hairdresserId ?>;

    fetch(`/api/hairdressers/${hairdresserId}/availability`, {
        headers: { 'Accept': 'application/json' }
    })
    .then(response => {
        if (!response.ok) throw new Error('Failed to load');
        return response.json();
    })
    .then(data => {
        const workingDays = data.working_days ?? [];

        if (workingDays.length === 0) {
            container.innerHTML = '<div class="alert alert-info">No availability set for this hairdresser.</div>';
            return;
        }

        let rows = '';
        for (const dayNumber of workingDays) {
            const dayName = days[dayNumber] ?? 'Unknown';
            rows += `<tr><td>${dayName}</td></tr>`;
        }

        container.innerHTML = `
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle">
                    <thead>
                        <tr><th>Working Day</th></tr>
                    </thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>`;
    })
    .catch(() => {
        container.innerHTML = '<div class="alert alert-danger">Could not load availability.</div>';
    });
})();
</script>
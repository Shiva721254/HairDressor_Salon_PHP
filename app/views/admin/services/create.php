<?php
/** @var array<int,string> $errors */
/** @var array $old */
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');
?>

<h1 class="mb-3">Add Service</h1>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="/admin/services" class="card p-3" style="max-width: 720px;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">

    <div class="mb-3">
        <label class="form-label">Name</label>
        <input class="form-control" name="name" required
               value="<?= htmlspecialchars((string)($old['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="mb-3">
        <label class="form-label">Duration (minutes)</label>
        
        <!-- Hidden Input for Form Submission -->
        <input type="hidden" id="duration_minutes" name="duration_minutes" required value="<?= htmlspecialchars((string)($old['duration_minutes'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        
        <!-- Custom Dropdown -->
        <div class="custom-dropdown-wrapper">
            <button type="button" class="btn btn-outline-primary w-100 text-start d-flex justify-content-between align-items-center custom-dropdown-btn" id="durationBtn">
                <span id="durationLabel">Select duration...</span>
                <i class="bi bi-chevron-down"></i>
            </button>
            
            <div class="custom-dropdown-menu" id="durationMenu">
                <div class="custom-dropdown-item" data-value="" style="display: none;"></div>
                <div class="custom-dropdown-item" data-value="15">✓ 15 min</div>
                <div class="custom-dropdown-item" data-value="30">✓ 30 min</div>
                <div class="custom-dropdown-item" data-value="45">✓ 45 min</div>
                <div class="custom-dropdown-item" data-value="60">✓ 1 hour</div>
                <div class="custom-dropdown-item" data-value="75">✓ 1 hour 15 min</div>
                <div class="custom-dropdown-item" data-value="90">✓ 1 hour 30 min</div>
                <div class="custom-dropdown-item" data-value="120">✓ 2 hours</div>
                <div class="custom-dropdown-item" data-value="150">✓ 2 hours 30 min</div>
            </div>
        </div>
        
        <div class="form-text">Select from 15-minute increments</div>
    </div>

    <div class="mb-3">
        <label class="form-label">Price (€)</label>
        <input class="form-control" name="price" type="number" min="0" step="0.01" required
               value="<?= htmlspecialchars((string)($old['price'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    </div>

    <div class="d-flex gap-2">
        <button class="btn btn-primary" type="submit">Create</button>
        <a class="btn btn-outline-secondary" href="/admin/services">Cancel</a>
    </div>
</form>

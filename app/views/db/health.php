<h1 class="mb-3">Database Health Check</h1>

<?php if (($status ?? '') === 'OK'): ?>
    <div class="alert alert-success">
        PDO connection: <strong>OK</strong>
    </div>
<?php else: ?>
    <div class="alert alert-danger">
        PDO connection: <strong>FAILED</strong>
    </div>
<?php endif; ?>



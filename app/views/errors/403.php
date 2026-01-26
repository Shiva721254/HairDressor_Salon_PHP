<?php
$title = $title ?? 'Forbidden';
?>
<div class="container">
    <h1 class="h3 mb-3">403 - <?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
    <p>You don't have permission to access this resource.</p>
    <a class="btn btn-primary" href="/">Go back home</a>
</div>
